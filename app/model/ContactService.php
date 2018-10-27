<?php

namespace App\Model;

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Database\ConnectionException;
use Nette\FileNotFoundException;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use Tracy\Debugger;

/** Class provides CRUD functionality for Contacts.
 */
class ContactService {
	use SmartObject;
    const XMLNS_ATOM = "http://www.w3.org/2005/Atom";
    const XMLNS_GD = "http://schemas.google.com/g/2005";
    const XML_SCHEMA = "http://schemas.google.com/g/2005#kind";
    const XML_TERM = "http://schemas.google.com/contact/2008#contact";
    const DEFAULT_CONTACT_DESCRIPTION = "Domain Shared Contact created with http://gshared-contacts.appspot.com/";
	const CACHE_EXPIRATION = "10 minutes";

    /** @var APIService */
    private $apis;
    /** @var OAuthService */
    private $oauth;
	/** @var  Cache */
    private $cache;
    /** @var  User */
    private $user;

    /**
     * ContactService constructor.
     * @param APIService $apis
     * @param OAuthService $oa
     * @param User $user
     */
    public function __construct(APIService $apis, OAuthService $oa, User $user) {
        $this->apis = $apis;
        $this->oauth = $oa;
        if($user->getIdentity() == null)
            return;
        $this->user = $user->getIdentity()->getData();
		$this->cache = new Cache(new FileStorage('../temp'));
    }

    /** Get single contact
     * @param string $id ID of an existing Contact
     * @param bool $purge Whether cache should be updated
     * @return Contact|null
     */
	public function get($id, $purge = false)
	{
		//this should happen only if user displays detail of contact before he firstly displays list of contacts
		//so this will happen nearly never
		$cached = $this->cache->load($this->getDomain());
		if($purge) {
			$q = new APIQuery();
			$q->setTarget($this->getBaseTarget() . "/full/" . $id);
			$q->setMethod(APIQuery::HTTP_METHOD_GET);
			$q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
			try {
				$this->apis->send($q, $this->oauth->getAccessToken());
			} catch(ConnectionException $ex) {
				if($ex->getCode() == APIQuery::HTTP_RESPONSE_NOT_FOUND)
				    return null;
				throw $ex;
			}
			$xmlResponse = new \SimpleXMLElement($q->getResponse());
			$xmlContact = $this->singleContactFromXML($xmlResponse);
			$cached[$xmlContact->getId()] = $xmlContact;
			$this->cache->save($this->getDomain(), $cached, [ 'data' => self::CACHE_EXPIRATION ]);
			return $xmlContact;
		} else {
		    if(!isset($cached[$id]))
		        return null;
			return $cached[$id];
		}
	}

    /** Get filtered & ordered list of contacts
     * @param array $filter Rules to filter the contacts
     * @param array $order Rules to order the contacts
     * @param bool $purge Whether cache should be updated
     * @return array|null
     */
	public function getAll($filter, $order, $purge = false) {
		if(!$purge && ($cached = $this->cache->load($this->getDomain()))) {
			return $this->prepareContacts($filter, $order, $cached);
		}
        $q = new APIQuery();
        $q->setTarget($this->getBaseTarget() . "/full");
        $q->setMethod(APIQuery::HTTP_METHOD_GET);
        $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);

        $this->apis->send($q, $this->oauth->getAccessToken());

        $xmlResponse = new \SimpleXMLElement($q->getResponse());
        $xmlContacts = $this->contactsFromXML($xmlResponse);
		$this->cache->save($this->getDomain(), $xmlContacts, [ self::CACHE_EXPIRATION ]);
		return $this->prepareContacts($filter, $order, $xmlContacts);
    }

    /** Save new contact to Google Contacts
     * @param Contact $c
     * @return Contact
     */
    public function create(Contact $c) {
	    $xmlContact = $this->contactToXML($c);
	    $q = new APIQuery();
	    $q->setTarget($this->getBaseTarget() . "/full");
	    $q->setContentType(APIQuery::CONTENT_ATOM);
	    $q->setMethod(APIQuery::HTTP_METHOD_POST);
	    $q->setBody($xmlContact->asXML());
	    $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_CREATED);
	    $this->apis->send($q, $this->oauth->getAccessToken());
		$xmlResponse = new \SimpleXMLElement($q->getResponse());
		$newContact = self::singleContactFromXML($xmlResponse);
		$cached = $this->cache->load($this->getDomain());
		$cached[$newContact->getId()] = $newContact;
		$this->cache->save($this->getDomain(), $cached, [ self::CACHE_EXPIRATION ]);
		return $newContact;
    }

    /** Update the contact in Google Contacts
     * @param Contact $cn
     */
	public function update(Contact $cn)
	{
        $xmlContact = $this->contactToXML($cn);
        $q = new APIQuery();
        $q->setTarget($cn->getEditTarget());
        $q->setContentType(APIQuery::CONTENT_ATOM);
        $q->setMethod(APIQuery::HTTP_METHOD_PUT);
        $q->setBody($xmlContact->asXML());
        $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
        $this->apis->send($q, $this->oauth->getAccessToken());
		$xmlResponse = new \SimpleXMLElement($q->getResponse());
		$updatedContact = self::singleContactFromXML($xmlResponse);
		$cached = $this->cache->load($this->getDomain());
		$cached[$updatedContact->getId()] = $updatedContact;
		$this->cache->save($this->getDomain(), $cached, [ self::CACHE_EXPIRATION ]);
	}

    /** Delete the contact from Google Contacts
     * @param Contact $cn
     * @param bool $recache Whether removed contact should be removed from cache
     */
	public function delete(Contact $cn, $recache = true)
	{
        if($cn->getId() == null)
            throw new FileNotFoundException("This contact has no ID. It cannot be deleted.");
	    $xmlContact = $this->contactToXML($cn);
        $q = new APIQuery();
        $q->setContentType(APIQuery::CONTENT_ATOM);
        $q->setTarget($cn->getEditTarget());
        $q->setMethod(APIQuery::HTTP_METHOD_DELETE);
        $q->setBody($xmlContact->asXML());
        $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
        $this->apis->send($q, $this->oauth->getAccessToken());
        if($recache) {
            $cached = $this->cache->load($this->getDomain());
            unset($cached[$cn->getId()]);
            $this->cache->save($this->getDomain(), $cached, [self::CACHE_EXPIRATION]);
        }
	}

    /** Delete a group of contacts from Google Contacts
     * @param array $ids
     */
	public function deleteBatch(array $ids) {
	    $contacts = $this->getAll(null,null,false);
	    foreach ($ids as $id) {
	        if(isset($contacts[$id])) {
                $this->delete($contacts[$id], false);
	            unset($contacts[$id]);
            }
        }
        $this->cache->save($this->getDomain(), $contacts, [ self::CACHE_EXPIRATION ]);
    }

    /** Save uploaded image as Contact's profile photo and send it to Google Contacts
     * @param Contact $c
     * @param SerializableImage $img
     */
	public function saveProfilePhoto(Contact $c, SerializableImage $img) {
		$c->setProfilePhoto($img);
        if($c->getId() == null)
            throw new FileNotFoundException("This contact has no ID. Profile picture cannot be set.");
        if($c->getProfilePhoto() == null)
            throw new FileNotFoundException("This contact has no profile photo set.");
		$c->setProfilePhoto($img);
		$cached = $this->cache->load($this->getDomain());
		$cached[$c->getId()] = $c;
		$this->cache->save($this->getDomain(), $cached, [ self::CACHE_EXPIRATION ]);
        $q = new APIQuery();
        $q->setTarget($c->getPhotoTarget());
        $q->setMethod(APIQuery::HTTP_METHOD_PUT);
        $q->setContentType('image/png');
        $q->setBody($img->toString(SerializableImage::PNG));
        $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
        $this->apis->send($q, $this->oauth->getAccessToken());
		while(true) {
			try {
				$img = $this->getProfilePhoto($c->getPhotoTarget());
				break;
			} catch (ConnectionException $ex) {
				//waiting while Google Server processes request to update photo
				//requests end with 404 until request is processed
			}
		}
    }

    /** Get domain of currently logged in administrator
     * @return string
     */
	public function getDomain() {
	    return explode('@', $this->user->getEmail())[1];
    }

    /** Get URL that is target for most of the requests
     * @return string
     */
    public function getBaseTarget() {
        return "https://www.google.com/m8/feeds/contacts/" . $this->getDomain();
    }

    /** Parse XML response to Contacts
     * @param \SimpleXMLElement $xml
     * @return Contact|Contact[]
     */
    private function contactsFromXML( \SimpleXMLElement &$xml ) {
	    if(!is_array($xml->entry) && $xml->getName() == "entry")
	        return $this->singleContactFromXML($xml);
        $contacts = [];
	    foreach ($xml->entry as $c) {
	    	/** @var Contact $contact */
	    	$contact = $this->singleContactFromXML($c);
	        $contacts[$contact->getId()] = $contact;
        }
        return $contacts;
    }

    /** Get target url for profile photo upload from the XML response
     * @param \SimpleXMLElement $xml
     * @return string
     */
    private function getNewPhotoURL( \SimpleXMLElement &$xml ) {
		return (string)$xml->xpath("./*[name()='link'][@rel='self']")[0]["href"];
	}

    /** Parse XML response as a single contact
     * @param \SimpleXMLElement $xml
     * @return Contact
     */
    private function singleContactFromXML( \SimpleXMLElement &$xml ) {
        $contact = new Contact();
        $tmpId = explode('/', $xml->id);
        $contact->setId($tmpId[count($tmpId) - 1]);
        $contact->setEditTarget((string)$xml->xpath("./*[name()='link'][@rel='edit']")[0]["href"]);
        $contact->setEditTime(new DateTime($xml->updated));
        $this->parseContact($xml, $contact);
        return $contact;
    }

    /** Parse contact attributes to contact object representation
     * @param \SimpleXMLElement $xc
     * @param Contact $c
     */
    private function parseContact(\SimpleXMLElement &$xc, Contact &$c) {
	    $c->setName((new NameAttribute())->fromXML($xc));
	    $c->setAddress((new AddressAttribute())->fromXML($xc));
	    $c->setOrganization((new OrganizationAttribute())->fromXML($xc));
	    $phones = $xc->xpath('descendant::'.PhoneAttribute::ATR_NAME);
        $emails = $xc->xpath('descendant::'.EmailAttribute::ATR_NAME);
        $customs = $xc->xpath('descendant::'.CustomAttribute::ATR_NAME);
        $this->setPhoto($xc, $c);
	    foreach ($phones as $key => $p)
	        $c->addPhone((new PhoneAttribute())->fromXML($p));
        foreach ($emails as $key => $e)
            $c->addEmail((new EmailAttribute())->fromXML($e));
        foreach ($customs as $key => $a)
            $c->addCustomAttribute((new CustomAttribute())->fromXML($a));
    }

    /** Convert contact to XML
     * @param Contact $contact
     * @return \SimpleXMLElement
     */
    private function contactToXML(Contact &$contact) {
	    $entry = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><atom:entry xmlns:atom="'.self::XMLNS_ATOM.'" xmlns:gd="'.self::XMLNS_GD.'"></atom:entry>');
	    $category = $entry->addChild("atom:category",null);
        $category->addAttribute("scheme", self::XML_SCHEMA);
        $category->addAttribute("term", self::XML_TERM);
        $entry->addChild("atom:content", self::DEFAULT_CONTACT_DESCRIPTION);

        if($contact->getId() != null)
            $entry->addChild("id",$this->getBaseTarget() . '/base/' . $contact->getId());

        $attributes = $contact->getAttributes();
	    foreach ( $attributes as $atr) {
	        $atr->toXML($entry);
        }

        return $entry;
    }

    /** Parse contact and get its profile photo
     * @param \SimpleXMLElement $xc
     * @param Contact $c
     */
    private function setPhoto(\SimpleXMLElement &$xc, Contact &$c) {
        $photoLinkElement = $xc->xpath("./*[name()='link'][@rel='http://schemas.google.com/contacts/2008/rel#photo']")[0];
        $photoURL = (string)$photoLinkElement->attributes()['href'];
        $c->setPhotoTarget($photoURL);
        if(!isset($photoLinkElement->attributes('gd', true)['etag']))
            return;
        $c->setProfilePhoto($this->getProfilePhoto($photoURL));
    }

    /** Receive profile photo from Google
     * @param $target
     * @return SerializableImage
     */
    private function getProfilePhoto($target) {
        $q = new APIQuery();
        $q->setTarget($target);
        $q->setMethod(APIQuery::HTTP_METHOD_GET);
        $q->setExpectedResponseCode(APIQuery::HTTP_RESPONSE_OK);
        $this->apis->send($q, $this->oauth->getAccessToken());
        return SerializableImage::fromString($q->getResponse());
    }

    /** Filter & order the list of contacts according to criteria
     * @param array $filter Filter rules
     * @param array $order Order rule
     * @param Contact[] $contacts
     * @return Contact[]|null
     */
    private function prepareContacts($filter, $order, &$contacts) {
        if($filter != null)
            $this->filterContacts($filter, $contacts);
        if($order != null && !empty($order))
            $this->orderContacts($order, $contacts);

        return $contacts;
    }

    /** Apply filters to the list of Contacts
     * @param array $filters
     * @param Contact[] $contacts
     */
    private function filterContacts(array $filters, &$contacts) {
        foreach ($contacts as $key => $value) {
            if(!$this->isMatch($value, $filters))
                unset($contacts[$key]);
        }
    }

    /** Order list of contacts according to specified rule
     * @param array $orderRule
     * @param Contact[] $contacts
     */
    private function orderContacts(array $orderRule, &$contacts) {
        if($orderRule[0] == 'fullname') {
            if($orderRule[1] == "ASC") {
                usort($contacts, function ($first, $second) {
                    /** @var Contact $first
                     * @var Contact $second */
                    $r = strcmp($first->getName()->getFullName(), $second->getName()->getFullName());
                    return $r;
                });
            } else {
                usort($contacts, function ($first, $second) {
                    /** @var Contact $first
                     * @var Contact $second */
                    return 0 - strcmp($first->getName()->getFullName(), $second->getName()->getFullName());
                });
            }
        }
        if($orderRule[0] == 'updated') {
            if($orderRule[1] == "ASC") {
                usort($contacts, function ($first, $second) {
                    /** @var Contact $first
                     * @var Contact $second */
                    if($first->getEditTime() >= $second->getEditTime())
                        return 1;
                    else
                        return -1;
                });
            } else {
                usort($contacts, function ($first, $second) {
                    /** @var Contact $first
                     * @var Contact $second */
                    if($first->getEditTime() >= $second->getEditTime())
                        return -1;
                    else
                        return 1;
                });
            }
        }
        /*if($orderRules[0] == 'email') {
            if($orderRules[1] == "ASC") {
                usort($contacts, function ($first, $second) {
                    if($first->getEmails() == null)
                        return 1;
                    if($second->getEmails() == null)
                        return -1;
                    return strcmp($first->getEmails()[0]->getValue(), $second->getEmails()[0]->getValue());
                });
            } else {
                usort($contacts, function ($first, $second) {
                    if($first->getEmails() == null)
                        return 1;
                    if($second->getEmails() == null)
                        return -1;
                    return 0 - strcmp($first->getEmails()[0]->getValue(), $second->getEmails()[0]->getValue());
                });
            }
        }
        if($orderRules[0] == 'phone') {
            if($orderRules[1] == "ASC") {
                usort($contacts, function ($first, $second) {
                    if($first->getPhones() == null)
                        return 1;
                    if($second->getPhones() == null)
                        return -1;
                    return strcmp($first->getPhones()[0]->getValue(), $second->getPhones()[0]->getValue());
                });
            } else {
                usort($contacts, function ($first, $second) {
                    if($first->getPhones() == null)
                        return 1;
                    if($second->getPhones() == null)
                        return -1;
                    return 0 - strcmp($first->getPhones()[0]->getValue(), $second->getEmails()[0]->getValue());
                });
            }
        }*/
    }

    /** Determine whether the contact matches filter criteria or not
     * @param Contact $c
     * @param array $f
     * @return bool
     */
    private function isMatch(Contact $c, array &$f) {
        if(isset($f['email']) && $f['email'] != '') {
            if(!$this->isEmailMatch($c->getEmails(), $f['email']))
                return false;
        }
        if(isset($f['phone']) && $f['phone'] != '') {
            if(!$this->isPhoneMatch($c->getPhones(), $f['phone']))
                return false;
        }
        if(isset($f['fullname']) && $f['fullname'] != '') {
            if(strpos(strtolower($c->getName()->getFullName()), strtolower($f['fullname'])) === false)
                return false;
        }
        return true;
    }

    /** Determine whether any of the emails match the filter criteria
     * @param EmailAttribute[] $emails
     * @param string $query
     * @return bool
     */
    private function isEmailMatch($emails, $query) {
        $query = strtolower($query);
        foreach ($emails as $m)
            if(strpos(strtolower($m->getValue()), $query) !== false)
                return true;

        return false;
    }

    /** Determine whether any of the phone numbers match the filter criteria
     * @param PhoneAttribute[] $phones
     * @param string $query
     * @return bool
     */
    private function isPhoneMatch($phones, $query) {
        $query = str_replace(' ', '', strtolower($query));
        foreach ($phones as $p)
            if(strpos(str_replace(' ', '', strtolower($p->getValue())), $query) !== false)
                return true;

        return false;
    }

}
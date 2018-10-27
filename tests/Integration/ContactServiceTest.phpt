<?php

namespace App\Tests\Integration;

require_once '../bootstrap.php';

use App\Model\APIService;
use App\Model\Contact;
use App\Model\ContactService;
use App\Model\EmailAttribute;
use App\Model\GoogleOAuth2Authenticator;
use App\Model\NameAttribute;
use App\Model\OAuthService;
use App\Model\OrganizationAttribute;
use App\Model\PhoneAttribute;
use App\Model\UserIdentity;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Http\UrlScript;
use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IUserStorage;
use Nette\Security\User;
use Tester\Assert;
use Tester\TestCase;

class SimulatedSessionSection extends SessionSection
{
    public $accessToken = "ya29.GlxtBOVKAK74Aes6pbaF0A3eX-gXuN3_4dLa-u4MRscp6Uh3JTT7eStjltH9P80RjZZotGuBbx6nmfaF8KeU1DWWNfnOnza0Xuzp3tkAZBYK3BiKfVHgvO1lOFvs8A";
    public function __construct(Session $session, $name) { }
}

class SimulatedSession extends Session
{
    public function __construct(IRequest $request, IResponse $response) {}
    public function getSection($section, $class = SessionSection::class) {
        return new SimulatedSessionSection($this,"");
    }
}

class SimulatedUser extends User
{
    /** @var \App\Model\User */
    private $userData = null;

    public function __construct(IUserStorage $storage = null, IAuthenticator $authenticator = NULL, IAuthorizator $authorizator = NULL) {}
    public function getIdentity() {
        return new UserIdentity($this->userData);
    }
    public function setUserData(\App\Model\User $userData) {
        $this->userData = $userData;
    }
}

class ContactServiceTest extends TestCase
{
    /** @var OAuthService */
    private $oauth;
    /** @var APIService */
    private $apis;
    /** @var ContactService */
    private $contactService;

    public function setUp() {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->oauth = new OAuthService(new SimulatedSession(new Request(new UrlScript()),new Response()));
        $this->apis = new APIService();
        $authenticator = new GoogleOAuth2Authenticator($this->oauth);
        $userData = $authenticator->getUserFromGoogle();
        $netteUser = new SimulatedUser();
        $netteUser->setUserData($userData);
        $this->contactService = new ContactService($this->apis, $this->oauth,$netteUser);
    }

    public function createTestContact() {
        $c = new Contact();
        $name = new NameAttribute();
        $name->setPrefix("Mr.");
        $name->setGivenName("First");
        $name->setAdditionalName("Middle");
        $name->setFamilyName("Last");
        $name->setSuffix("Best");
        $c->setName($name);
        $c->addEmail(new EmailAttribute("home@email.com",EmailAttribute::EMAIL_HOME));
        $c->addEmail(new EmailAttribute("work@email.com",EmailAttribute::EMAIL_WORK));
        $c->addEmail(new EmailAttribute("other@email.com",EmailAttribute::EMAIL_OTHER));
        $c->addPhone(new PhoneAttribute("123123123", PhoneAttribute::PHONE_HOME));
        $c->addPhone(new PhoneAttribute("456456456", PhoneAttribute::PHONE_WORK));
        $c->addPhone(new PhoneAttribute("789789789", PhoneAttribute::PHONE_MOBILE));
        $c->addPhone(new PhoneAttribute("912912912", PhoneAttribute::PHONE_FAX));
        $org = new OrganizationAttribute();
        $org->setName("Company name");
        $org->setDepartment("Department");
        $org->setJobTitle("Job Title");
        $org->setJobDescription("Job description");
        $c->setOrganization($org);
        return $c;
    }

    public function validateContacts(Contact $c1, Contact $c2) {
        Assert::equal($c1->getName()->getFullName(), $c2->getName()->getFullName());
        Assert::equal($c1->getOrganization(), $c2->getOrganization());
        $emails1 = $c1->getEmails();
        $emails2 = $c2->getEmails();
        $phones1 = $c1->getPhones();
        $phones2 = $c2->getPhones();
        Assert::equal($emails1[0]->getValue(), $emails2[0]->getValue());
        Assert::equal($emails1[0]->getType(), $emails2[0]->getType());
        Assert::equal($emails1[1]->getValue(), $emails2[1]->getValue());
        Assert::equal($emails1[1]->getType(), $emails2[1]->getType());
        Assert::equal($emails1[2]->getValue(), $emails2[2]->getValue());
        Assert::equal($emails1[2]->getType(), $emails2[2]->getType());
        Assert::equal($phones1[0]->getValue(), $phones2[0]->getValue());
        Assert::equal($phones1[0]->getType(), $phones2[0]->getType());
        Assert::equal($phones1[1]->getValue(), $phones2[1]->getValue());
        Assert::equal($phones1[1]->getType(), $phones2[1]->getType());
        Assert::equal($phones1[2]->getValue(), $phones2[2]->getValue());
        Assert::equal($phones1[2]->getType(), $phones2[2]->getType());
    }

    public function testConnection() {
        $this->contactService->getAll(null,null,true);
    }

    public function testCreate() {
        $c = $this->createTestContact();
        $new = $this->contactService->create($c);
        $this->validateContacts($c,$new);
        $this->contactService->delete($new);
    }

    public function testCreateAndGet() {
        $tocreate = $this->createTestContact();
        $created = $this->contactService->create($tocreate);
        $found = $this->contactService->get($created->getId());
        $this->validateContacts($tocreate, $found);
        $this->contactService->delete($found);
    }

    public function testDelete() {
        $tocreate = $this->createTestContact();
        $created = $this->contactService->create($tocreate);
        $this->contactService->delete($created);
        Assert::null($this->contactService->get($created->getId()));
    }

    public function testDeleteMultiple() {
        $c1 = $this->createTestContact();
        $c1 = $this->contactService->create($c1);
        $c2 = $this->createTestContact();
        $c2 = $this->contactService->create($c2);
        $this->contactService->deleteBatch([$c1->getId(), $c2->getId()]);
        $all = $this->contactService->getAll(null,null);
        Assert::true(!isset($all[$c1->getId()]));
        Assert::true(!isset($all[$c2->getId()]));
    }
}

(new ContactServiceTest())->run();
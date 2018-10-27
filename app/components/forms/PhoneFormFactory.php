<?php

namespace App\Components\Forms;

use App\Model\PhoneAttribute;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

/** Class handles process of creating phone attributes of currently edit(created) Contact.
 * Form uses session storage and saves the attributes to it. Once the whole Contact form is submitted entries
 * are saved with the contact and session is cleared.
 */
class PhoneFormFactory {
	use SmartObject;

    const SESSION_SECTION = "contact-phones";
    const VALUE_FIELD = "gd_phone_value";
    const TYPE_FIELD = "gd_phone_type";

    /** @var  Session */
    private $session;

    /**
     * PhoneFormFactory constructor.
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->session = $session;
    }

    /** Create the form
     * @return Form
     */
    public function createAddPhoneForm() {
        $f = new Form(null, "addPhoneForm");
        $f->addProtection("This form is protected against CSRF!");
        $f->elementPrototype->addAttributes(array('class' => 'ajax'));
        $f->addGroup("Phone contacts");
        $f->addText($this::VALUE_FIELD)->setType('tel')->setAttribute("placeholder", "Phone number");
        $f->addSelect($this::TYPE_FIELD, null, array(
            PhoneAttribute::PHONE_HOME => 'Home',
            PhoneAttribute::PHONE_WORK => 'Work',
            PhoneAttribute::PHONE_MOBILE => 'Mobile',
            PhoneAttribute::PHONE_FAX => 'Fax'
        ));
        $f->addSubmit('add_phone','Add phone')->getControlPrototype()->setAttribute("class","btn");
        $f->onSuccess[] = array($this, 'addPhoneFormSuccess');
        return $f;
    }

    /** Save the form response to storage and clear the form
     * @param Form $form
     * @param ArrayHash $data
     */
    public function addPhoneFormSuccess(Form $form, ArrayHash $data) {
        if($data[$this::VALUE_FIELD] == "")
            return;
        self::addEntry($this->session, new PhoneAttribute($data[$this::VALUE_FIELD], $data[$this::TYPE_FIELD]));
        $form[self::VALUE_FIELD]->setValue('');
    }

    /** Empty the storage session storage
     * @param Session $s
     */
    public static function releaseEntries(Session $s) {
        if(!$s->hasSection(PhoneFormFactory::SESSION_SECTION))
            return;
        unset($s->getSection(PhoneFormFactory::SESSION_SECTION)->phones);
    }

    /** Get all phone attributes from session storage
     * @param Session $s
     * @return PhoneAttribute[]|null
     */
    public static function getEntries(Session $s) {
        if(!$s->hasSection(PhoneFormFactory::SESSION_SECTION))
            return null;
        return $s->getSection(PhoneFormFactory::SESSION_SECTION)->phones;
    }

    /** Remove single phone attribute from session storage
     * @param Session $s
     * @param $id
     */
    public static function removeEntry(Session $s, $id) {
        if(!$s->hasSection(PhoneFormFactory::SESSION_SECTION))
            return;
        unset($s->getSection(PhoneFormFactory::SESSION_SECTION)->phones[$id]);
    }

    /** Add phone attribute to the session storage
     * @param Session $s
     * @param PhoneAttribute $atr
     */
    public static function addEntry(Session $s, PhoneAttribute $atr) {
        $s->getSection(self::SESSION_SECTION)->phones[] = $atr;
    }
}
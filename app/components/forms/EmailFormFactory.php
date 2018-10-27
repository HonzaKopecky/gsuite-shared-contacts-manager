<?php

namespace App\Components\Forms;

use App\Model\EmailAttribute;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

/** Class handles process of creating email attributes of currently edit(created) Contact.
 * Form uses session storage and saves the attributes to it. Once the whole Contact form is submitted entries
 * are saved with the contact and session is cleared.
 */
class EmailFormFactory {
	use SmartObject;

    const SESSION_SECTION = "contact-emails";
    const VALUE_FIELD = "gd_email_value";
    const TYPE_FIELD = "gd_email_type";

    /** @var  Session */
    private $session;

    /**
     * EmailFormFactory constructor.
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->session= $session;
    }

    /** Create the form
     * @return Form
     */
    public function createAddEmailForm() {
        $f = new Form(null, "addEmailForm");
        $f->addProtection("This form is protected against CSRF!");
        $f->elementPrototype->addAttributes(array('class' => 'ajax'));
        $f->addGroup("Email contacts");
        $f->addEmail($this::VALUE_FIELD)->setType('email')->setAttribute("placeholder","Email address");
        $f->addSelect($this::TYPE_FIELD, null, array(
            EmailAttribute::EMAIL_HOME => 'Home',
            EmailAttribute::EMAIL_WORK => 'Work',
            EmailAttribute::EMAIL_OTHER => 'Other'
        ));
        $f->addSubmit('add_email','Add email')->getControlPrototype()->setAttribute("class","btn");
        $f->onSuccess[] = array($this, 'addEmailFormSuccess');
        return $f;
    }

    /** Save the form response to storage and clear the form
     * @param Form $form
     * @param ArrayHash $data
     */
    public function addEmailFormSuccess(Form $form, ArrayHash $data) {
        if($data[$this::VALUE_FIELD] == "")
            return;
        self::addEntry($this->session, new EmailAttribute($data[$this::VALUE_FIELD], $data[$this::TYPE_FIELD]));
        $form[self::VALUE_FIELD]->setValue('');
    }

    /** Empty the storage session storage
     * @param Session $s
     */
    public static function releaseEntries(Session $s) {
        if(!$s->hasSection(EmailFormFactory::SESSION_SECTION))
            return;
        unset($s->getSection(EmailFormFactory::SESSION_SECTION)->emails);
    }

    /** Get all email attributes from session storage
     * @param Session $s
     * @return EmailAttribute[]|null
     */
    public static function getEntries(Session $s) {
        if(!$s->hasSection(EmailFormFactory::SESSION_SECTION))
            return null;
        return $s->getSection(EmailFormFactory::SESSION_SECTION)->emails;
    }

    /** Remove single email attribute from session storage
     * @param Session $s
     * @param $id
     */
    public static function removeEntry(Session $s, $id) {
        if(!$s->hasSection(EmailFormFactory::SESSION_SECTION))
            return;
        unset($s->getSection(EmailFormFactory::SESSION_SECTION)->emails[$id]);
    }

    /** Add custom attribute to the session storage
     * @param Session $s
     * @param EmailAttribute $atr
     */
    public static function addEntry(Session $s, EmailAttribute $atr) {
        $s->getSection(self::SESSION_SECTION)->emails[] = $atr;
    }
}
<?php

namespace App\Components\Forms;

use App\Model\AddressAttribute;
use App\Model\Contact;
use App\Model\ContactService;
use App\Model\CustomAttribute;
use App\Model\EmailAttribute;
use App\Model\NameAttribute;
use App\Model\OrganizationAttribute;
use App\Model\PhoneAttribute;
use Nette\Application\UI\Form;
use Nette\Database\ConnectionException;
use Nette\Http\Session;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

/** Class handles Create/Edit Contact Form processing and the update/create process.
 */
class ContactFormFactory {
	use SmartObject;

    const FIELD_PREFIX = "gd_name_prefix";
    const FIELD_GIVEN = "gd_given_name";
    const FIELD_ADD = "gd_additional_name";
    const FIELD_FAMILY = "gd_family_name";
    const FIELD_SUFFIX = "gd_name_suffix";
    const FIELD_ID = "gd_id";
    const FIELD_EDIT_TARGET = "gd_edit_target";
    const FIELD_STREET = "gd_address_street";
    const FIELD_CITY = "gd_address_city";
    const FIELD_POSTCODE = "gd_address_postcode";
    const FIELD_REGION = "gd_address_region";
    const FIELD_COUNTRY = "gd_address_country";
    const FIELD_ORG_NAME = "gd_organization_name";
    const FIELD_ORG_DEPARTMENT = "gd_organization_department";
    const FIELD_JOB_TITLE = "gd_job_title";
    const FIELD_JOB_DESCRIPTION = "gd_job_description";
    const FORM_NAME_CREATE = "createContactForm";
    const FORM_NAME_EDIT = "editContactForm";

    /** @var ContactService
      * @inject
     */
    public $contactService;

    /** @var Session */
    private $session;

    /** @var Contact */
    private $contact;

    /**
     * ContactFormFactory constructor.
     * @param ContactService $cs
     * @param Session $session
     */
    public function __construct(ContactService $cs, Session $session) {
        $this->contactService = $cs;
        $this->session = $session;
    }

    /** Create form to create a contact.
     * @return Form
     */
    public function createCreateContactForm() {
        $form = new Form(null,self::FORM_NAME_CREATE);
        $form->addProtection("This form is protected against CSRF!");
        $this->addFields($form);
        $form->onSuccess[] = [$this,"contactFormSuccess"];
        $form->onValidate[] = [$this, "validateForm"];
        $form->onRender[] = function(Form $form) {
            if(!$form->isSubmitted()) {
                //every time the contact form is being rendered (not on validation erro)
                //release list of emails and phones
                //that might remain in session from previous uncompleted form
                EmailFormFactory::releaseEntries($this->session);
                PhoneFormFactory::releaseEntries($this->session);
                CustomAttributeFromFactory::releaseEntries($this->session);
            }
        };
        return $form;
    }

    /** Create form to edit the contact. Fill it with current contact data.
     * @param Contact $contact Contact to edit
     * @return Form
     */
    public function createEditContactForm(Contact &$contact) {
        $this->contact = $contact;
        $form = new Form(null, self::FORM_NAME_EDIT);
        $form->addProtection("This form is protected against CSRF!");
        $this->addFields($form);
        $form->addHidden(self::FIELD_ID, $contact->getId());
        $form->addHidden(self::FIELD_EDIT_TARGET, $contact->getEditTarget());
        $form->onSuccess[] = [$this,"contactFormSuccess"];
        $form->onValidate[] = [$this, "validateForm"];
        $form->onRender[] = [$this, 'addDefaults'];
        return $form;
    }

    /** Callback on success of the create/edit contact form. Will update existing or create a new contact in Google Contacts.
     * Whether contact was created or edited is determined by the name of the form.
     * @param Form $form
     * @param ArrayHash $values
     */
    public function contactFormSuccess(Form $form, ArrayHash $values) {
        $emails = EmailFormFactory::getEntries($this->session);
        $phones = PhoneFormFactory::getEntries($this->session);
        $customs = CustomAttributeFromFactory::getEntries($this->session);

        $c = new Contact();
        $this->setName($c, $values);
        $this->setAddress($c, $values);
        $this->setOrganization($c, $values);
        $this->setEmails($c, $emails);
        $this->setPhones($c, $phones);
        $this->setCustomAttributes($c, $customs);

        try {
            if($form->getName() == self::FORM_NAME_CREATE)
                $this->contactService->create($c);

            if($form->getName() == self::FORM_NAME_EDIT) {
                $c->setEditTarget($values[self::FIELD_EDIT_TARGET]);
                $c->setId($values[self::FIELD_ID]);
                $this->contactService->update($c);
            }
        } catch (ConnectionException $ex) {
            $form->getParent()->solveConnectionException($ex);
        }

        //release list of emails and phones that was used to create contact
        EmailFormFactory::releaseEntries($this->session);
        PhoneFormFactory::releaseEntries($this->session);
        CustomAttributeFromFactory::releaseEntries($this->session);
    }

    /** Validate form response.
     * It needs to contain at least first and last name. There should be also a specified maximum of custom attributes.
     * @param Form $form
     * @param ArrayHash $values
     */
    public function validateForm(Form $form, ArrayHash $values) {
        if($values[self::FIELD_GIVEN] == "" && $values[self::FIELD_FAMILY] == "")
            $form->addError("You need to specify at least one part of name.");
        $customs = CustomAttributeFromFactory::getEntries($this->session);
        if(count((array)$customs) > CustomAttribute::MAXIMUM_AMOUNT)
            $form->addError("You cannot add more than ".CustomAttribute::MAXIMUM_AMOUNT." custom attributes. 
            Delete at least " . (count($customs) - CustomAttribute::MAXIMUM_AMOUNT) . " entries.");
    }

    /** Setup form fields
     * @param Form $form
     */
    private function addFields(Form $form) {
        //Name section
        $form->addGroup('Name');
        $form->addText($this::FIELD_PREFIX, "Prefix")->setAttribute('placeholder', 'Prefix');
        $form->addText($this::FIELD_GIVEN)->setAttribute('placeholder', 'First name');
        $form->addText($this::FIELD_ADD)->setAttribute('placeholder', 'Middle name');
        $form->addText($this::FIELD_FAMILY)->setAttribute('placeholder', 'Last name');
        $form->addText($this::FIELD_SUFFIX)->setAttribute('placeholder', 'Suffix');
        //Address section
        $form->addGroup('Address');
        $form->addText($this::FIELD_STREET)->setAttribute('placeholder', 'Street, house number');
        $form->addText($this::FIELD_POSTCODE)->setAttribute('placeholder', 'Postcode');
        $form->addText($this::FIELD_CITY)->setAttribute('placeholder', 'City');
        $form->addText($this::FIELD_REGION)->setAttribute('placeholder', 'Region');
        $form->addText($this::FIELD_COUNTRY)->setAttribute('placeholder', 'Country');
        //Organization section
        $form->addGroup('Organization');
        $form->addText($this::FIELD_ORG_NAME)->setAttribute('placeholder', 'Organization name');
        $form->addText($this::FIELD_ORG_DEPARTMENT)->setAttribute('placeholder', 'Department');
        $form->addText($this::FIELD_JOB_TITLE)->setAttribute('placeholder', 'Job title');
        $form->addTextArea($this::FIELD_JOB_DESCRIPTION)->setAttribute('placeholder', 'Job description');
    }

    /** Fill the form with current contact data
     * @param Form $f
     */
    public function addDefaults(Form $f) {
        //Name section
        $n = $this->contact->getName();
        $f[self::FIELD_PREFIX]->setDefaultValue($n->getPrefix());
        $f[self::FIELD_GIVEN]->setDefaultValue($n->getGivenName());
        $f[self::FIELD_ADD]->setDefaultValue($n->getAdditionalName());
        $f[self::FIELD_FAMILY]->setDefaultValue($n->getFamilyName());
        $f[self::FIELD_SUFFIX]->setDefaultValue($n->getSuffix());
        //Address section
        $a = $this->contact->getAddress();
        $f[self::FIELD_STREET]->setDefaultValue($a->getStreet());
        $f[self::FIELD_POSTCODE]->setDefaultValue($a->getPostCode());
        $f[self::FIELD_CITY]->setDefaultValue($a->getCity());
        $f[self::FIELD_REGION]->setDefaultValue($a->getRegion());
        $f[self::FIELD_COUNTRY]->setDefaultValue($a->getCountry());
        //Organization section
        $o = $this->contact->getOrganization();
        $f[self::FIELD_ORG_NAME]->setDefaultValue($o->getName());
        $f[self::FIELD_ORG_DEPARTMENT]->setDefaultValue($o->getDepartment());
        $f[self::FIELD_JOB_TITLE]->setDefaultValue($o->getJobTitle());
        $f[self::FIELD_JOB_DESCRIPTION]->setDefaultValue($o->getJobDescription());
    }

    /** Parse form data and set the name attribute of the contact
     * @param Contact $c
     * @param ArrayHash $values
     */
    private function setName(Contact &$c, ArrayHash &$values) {
        $name = new NameAttribute();
        $name->setPrefix($values[$this::FIELD_PREFIX]);
        $name->setGivenName($values[$this::FIELD_GIVEN]);
        $name->setAdditionalName($values[$this::FIELD_ADD]);
        $name->setFamilyName($values[$this::FIELD_FAMILY]);
        $name->setSuffix($values[$this::FIELD_SUFFIX]);
        $c->setName($name);
    }

    /** Parse form data and set the address attribute of the contact
     * @param Contact $c
     * @param ArrayHash $values
     */
    private function setAddress(Contact &$c, ArrayHash &$values) {
        $address = new AddressAttribute();
        $address->setStreet($values[self::FIELD_STREET]);
        $address->setPostCode($values[self::FIELD_POSTCODE]);
        $address->setCity($values[self::FIELD_CITY]);
        $address->setRegion($values[self::FIELD_REGION]);
        $address->setCountry($values[self::FIELD_COUNTRY]);
        $c->setAddress($address);
    }

    /** Parse form data and set the organization attribute of the contact
     * @param Contact $c
     * @param ArrayHash $values
     */
    private function setOrganization(Contact &$c, ArrayHash &$values) {
        $org = new OrganizationAttribute();
        $org->setName($values[self::FIELD_ORG_NAME]);
        $org->setDepartment($values[self::FIELD_ORG_DEPARTMENT]);
        $org->setJobTitle($values[self::FIELD_JOB_TITLE]);
        $org->setJobDescription($values[self::FIELD_JOB_DESCRIPTION]);
        $c->setOrganization($org);
    }

    /** Set email attributes to the contact
     * @param Contact $c
     * @param EmailAttribute[] $emails
     */
    private function setEmails(Contact &$c, $emails) {
        if($emails == null)
            return;
        foreach ($emails as $i => $email)
            $c->addEmail($email);
    }

    /** Set phone attributes to the contact
     * @param Contact $c
     * @param PhoneAttribute[] $phones
     */
    private function setPhones(Contact &$c, $phones) {
        if ($phones == null)
            return;
        foreach ($phones as $i => $phone)
            $c->addPhone($phone);
    }

    /** Set custom attributes to the contact
     * @param Contact $c
     * @param CustomAttribute[] $customAttributes
     */
    private function setCustomAttributes(Contact &$c, $customAttributes) {
        if ($customAttributes == null)
            return;
        foreach ($customAttributes as $i => $attr)
            $c->addCustomAttribute($attr);
    }
}
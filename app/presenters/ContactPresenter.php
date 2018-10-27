<?php

namespace App\Presenters;

use App\Components\Forms\ContactFormFactory;
use App\Components\Forms\CustomAttributeFromFactory;
use App\Components\Forms\EmailFormFactory;
use App\Components\Forms\PhoneFormFactory;
use App\Components\PictureEditor;
use App\Model\APIService;
use App\Model\Contact;
use App\Model\ContactService;
use App\Model\MyDatagrid;
use App\Model\OAuthService;
use Nette\Application\UI\Form;
use Nette\Database\ConnectionException;
use Nette\Forms\Container;
use Nette\Http\Session;
use Nextras\Datagrid\Datagrid;
use Tracy\Debugger;

/** Presenter that handles all operations with Contacts
 */
class ContactPresenter extends SecuredPresenter
{
    const CREATED_CONTACT_ATTRIBUTE = "created_id";
    const UPDATED_CONTACT_ATTRIBUTE = "updated_id";

    /** @var APIService */
    private $apis;
    /** @var  ContactService */
    private $cs;
    /** @var  ContactFormFactory */
    private $contactFormFactory;
    /** @var  EmailFormFactory */
    private $emailFormFactory;
    /** @var  PhoneFormFactory */
    private $phoneFormFactory;
    /** @var CustomAttributeFromFactory */
    private $customAttributeFormFactory;
    /** @var  Session */
    private $session;
    /** @var  Contact */
    private $contact;

    /**
     * ContactPresenter constructor.
     * @param OAuthService $os
     */
	public function __construct(OAuthService $os)
	{
		parent::__construct($os);
	}

    /** Inject required dependencies
     * @param APIService $apis
     * @param ContactService $cs
     * @param ContactFormFactory $cff
     * @param EmailFormFactory $eff
     * @param PhoneFormFactory $pff
     * @param CustomAttributeFromFactory $caff
     * @param Session $session
     */
    public function injectDependencies(APIService $apis,
                                       ContactService $cs,
                                       ContactFormFactory $cff,
                                       EmailFormFactory $eff,
                                       PhoneFormFactory $pff,
                                       CustomAttributeFromFactory $caff,
                                       Session $session) {
        $this->apis = $apis;
        $this->cs = $cs;
        $this->contactFormFactory = $cff;
        $this->emailFormFactory = $eff;
        $this->phoneFormFactory = $pff;
        $this->customAttributeFormFactory = $caff;
        $this->session = $session;
    }

    /**
     * URL = /contact/
     * Receive all contacts. Clear cache if URL purge attribute is set.
     */
    public function actionDefault() {
        try {
            if ($this->getParameter("purge", false) !== false)
                $this->cs->getAll(null, null, true);
            else
                $this->cs->getAll(null, null, false);
        } catch (ConnectionException $ex) {
            $this->solveConnectionException($ex);
        }
    }

    /**
     * URL = /contact/delete/$id
     * Delete contact specified by the ID in URL
     * @param string $id ID of contact to be deleted
     */
    public function actionDelete($id) {
        $contact = null;
        try {
            $contact = $this->cs->get($id);
        } catch (ConnectionException $ex) {
            $this->solveConnectionException($ex);
        }
        if($contact == null) {
            $this->flashMessage("We could not delete this contact.");
            $this->redirect("default");
        }
        $this->cs->delete($contact);
        $this->flashMessage("Contact " . $contact->getName()->getName() . " successfuly deleted.");
        $this->redirect("default");
    }

    /**
     * URL = /contact/edit/$id
     * Prepare data for Contact Edit form
     * @param string $id ID of contact to be deleted
     */
    public function actionEdit($id) {
        $contact = null;
        try {
            $contact = $this->cs->get($id);
        } catch (ConnectionException $ex) {
            $this->solveConnectionException($ex);
        }
        if($contact == null) {
            $this->flashMessage("We could not find this contact.");
            $this->redirect("default");
        }
        $this->template->contact = $contact;
        $this->contact = $contact;
    }

    /**
     * URL = /contact/edit/$id
     * Clear repeating-value forms session and fill it with the attributes
     */
    public function renderEdit() {
        //if rendering is not caused by redrawControl or contact form validation error
        if($this->isAjax() || isset($_POST["_do"]))
            return;
        EmailFormFactory::releaseEntries($this->session);
        foreach ($this->contact->getEmails() as $email)
            EmailFormFactory::addEntry($this->session, $email);
        PhoneFormFactory::releaseEntries($this->session);
        foreach ($this->contact->getPhones() as $phone)
            PhoneFormFactory::addEntry($this->session, $phone);
        CustomAttributeFromFactory::releaseEntries($this->session);
        foreach ($this->contact->getCustomAttributes() as $customAttribute)
            CustomAttributeFromFactory::addEntry($this->session, $customAttribute);
        $this->template->emails = EmailFormFactory::getEntries($this->session);
        $this->template->phones = PhoneFormFactory::getEntries($this->session);
        $this->template->customs = CustomAttributeFromFactory::getEntries($this->session);
        $this->template->autofill = $this->prepareAutofill();
    }

    /**
     * URL = /contact/create
     * Assign autofill data to the template
     */
    public function renderCreate() {
        $this->template->autofill = $this->prepareAutofill();
    }

    /** Create form to create / edit Contact
     * @param Contact|null $c
     * @return Form
     */
    public function createContactForm(Contact $c = null) {
        if($c == null)
            $form = $this->contactFormFactory->createCreateContactForm();
        else
            $form = $this->contactFormFactory->createEditContactForm($c);

        $form->onError[] = function() {
            $this->template->emails = EmailFormFactory::getEntries($this->session);
            $this->template->phones = PhoneFormFactory::getEntries($this->session);
            $this->template->customs = CustomAttributeFromFactory::getEntries($this->session);
        };
        return $form;
    }

    /** Create form to edit contact
     * @return Form
     */
    public function createComponentEditContactForm() {
        $form = $this->createContactForm($this->contact);
        $form->onSuccess[] = function() {
            $this->flashMessage("Contact \"". $this->contact->getName()->getName() . "\" updated.");
            $this->redirect("edit", $this->contact->getId());
        };
        return $form;
    }

    /** Create form to create contact
     * @return Form
     */
    public function createComponentCreateContactForm() {
        $form = $this->createContactForm();
        $form->onSuccess[] = function() {
            $this->flashMessage("Contact created.");
            $this->redirect("create");
        };
        return $form;
    }

    /** Create form for repeating email values
     * @return Form
     */
    public function createComponentAddEmailForm() {
        $form = $this->emailFormFactory->createAddEmailForm();
        $form->onSuccess[] = function ($f) {
            $this->template->emails = EmailFormFactory::getEntries($this->session);
            $this->redrawControl('emailList');
            $this->redrawControl('emailsForm');
        };
        return $form;
    }

    /** Create form for repeating phone number values
     * @return Form
     */
    public function createComponentAddPhoneForm() {
        $form = $this->phoneFormFactory->createAddPhoneForm();
        $form->onSuccess[] = function (Form $form) {
            $this->template->phones = PhoneFormFactory::getEntries($this->session);
            $this->redrawControl('phoneList');
            $this->redrawControl('phonesForm');
        };
        return $form;
    }

    /** Create form for repeating custom attributes
     * @return Form
     */
    public function createComponentAddCustomAttributeForm() {
        $form = $this->customAttributeFormFactory->createAddCustomAttributeForm();
        $form->onSuccess[] = function () {
            $this->template->customs = CustomAttributeFromFactory::getEntries($this->session);
            $this->redrawControl('customsList');
            $this->redrawControl('customsForm');
        };
        $form->onError[] = function() {
            $this->redrawControl('customsForm');
        };
        return $form;
    }

    /** Handle signal to remove Email from session storage
     * @param $index
     */
    public function handleRemoveEmail($index) {
        EmailFormFactory::removeEntry($this->session, $index);
        $this->template->emails = EmailFormFactory::getEntries($this->session);
        $this->redrawControl('emailList');
    }

    /** Handle signal to remove Phone number from session storage
     * @param $index
     */
    public function handleRemovePhone($index) {
        PhoneFormFactory::removeEntry($this->session, $index);
        $this->template->phones = PhoneFormFactory::getEntries($this->session);
        $this->redrawControl('phoneList');
    }

    /** Handle signal to remove ¨Custom Attribute from session storage
     * @param $index
     */
    public function handleRemoveCustomAttribute($index) {
        CustomAttributeFromFactory::removeEntry($this->session, $index);
        $this->template->customs = CustomAttributeFromFactory::getEntries($this->session);
        $this->redrawControl('customsList');
    }

    /** Create PictureEditor component
     * @return PictureEditor
     */
    public function createComponentPictureEditor() {
        return new PictureEditor($this->cs, $this->contact);
    }

    /** Handle any connection exception that might occur during data retrieving. If the access token had expired, reauthenticate, otherwise display error.
     * @param ConnectionException $ex
     */
    public function solveConnectionException(ConnectionException $ex) {
        if($ex->getCode() == APIService::CODE_AUTH) {
            $this->flashMessage("Your Google access token has expired so you have been redirected to Google to renew it. Everything is just fine ;-)");
            $this->redirect('Login:reauth');
        } else {
            $this->flashMessage("Oh. There was a problem during connection with Google. Try it again.");
            $this->redirect("Dashboard:default");
        }
    }

    /** Create my customized datagrid for the list of Contacts
     * @return MyDatagrid
     */
    public function createComponentDatagrid()
    {
        $grid = new MyDatagrid($this->cs);
        $grid->addColumn('id', '');
        $grid->addColumn('photo',' ');
        $grid->addColumn('fullname',"Name")->enableSort(Datagrid::ORDER_ASC);
        $grid->addColumn('email',"Email");
        $grid->addColumn('phone',"Phone");
        $grid->addColumn('updated',"Updated")->enableSort();
        $grid->setDataSourceCallback([ $this, 'prepareData' ]);

        $grid->addCellsTemplate(__DIR__ . '/templates/Contact/Datagrid/@cells.latte');

        $grid->setFilterFormFactory( function() {
           $f = new Container();
           $f->addText('fullname')->setAttribute("placeholder", "Filter by Name");
           $f->addText('phone')->setAttribute("placeholder", "Filter by Phone");;
           $f->addText('email')->setAttribute("placeholder", "Filter by Email");;
           $f->addSubmit('filter', "Filter");
           return $f;
        });

        //this is needed to display table footer
        $grid->setPagination(10000, function($c) {});

        $grid->addGlobalAction('delete', 'Delete', function (array $ids, Datagrid $grid) {
            $this->cs->deleteBatch($ids);
            $grid->redrawControl('rows');
        });

        return $grid;
    }

    /** Method suplies data for the Datagrid
     * @param array $filters Filter rules specified by user
     * @param array $order Order rule specified by user
     * @return Contact[]|null
     */
    public function prepareData($filters, $order) {
        $contacts = null;
        if(isset($_POST['filter']))
            $filters = $_POST['filter'];
        try {
            if ($this->getParameter("purge", false) !== false)
                return $this->cs->getAll($filters, $order, true);
            else
                return $this->cs->getAll($filters, $order, false);
        } catch (ConnectionException $ex) {
            return null;
        }
    }

    /** Collect all autofill data
     * @return array
     */
    private function prepareAutofill() {
        $contacts = $this->cs->getAll(null,null,false);
        $autofill = array();
        foreach ($contacts as $c) {
            $this->prepareAutofillAddress($c, $autofill['address']);
            $this->prepareAutofillOrganization($c, $autofill['organization']);
            $this->prepareAutofillCustoms($c, $autofill['customs']);
        }
        return $autofill;
    }

    /** Append the value to the array if the value is not null
     * @param array $array
     * @param mixed $value
     */
    private function addIfNotNull(&$array, $value) {
        if($value != null && $value != '')
            $array[] = $value;
    }

    /** Collect address data for Autofill feature
     * @param Contact $c
     * @param array $array
     */
    private function prepareAutofillAddress(Contact &$c, &$array) {
        $a = $c->getAddress();
        if($a == null)
            return;
        $this->addIfNotNull($array['street'], $a->getStreet());
        $this->addIfNotNull($array['postcode'], $a->getPostCode());
        $this->addIfNotNull($array['city'], $a->getCity());
        $this->addIfNotNull($array['region'], $a->getRegion());
        $this->addIfNotNull($array['country'], $a->getCountry());
    }

    /** Collect organization data for Autofill feature
     * @param Contact $c
     * @param array $array
     */
    private function prepareAutofillOrganization(Contact &$c, &$array) {
        $o = $c->getOrganization();
        if($o == null)
            return;
        $this->addIfNotNull($array['organization'], $o->getName());
        $this->addIfNotNull($array['department'], $o->getDepartment());
        $this->addIfNotNull($array['job'], $o->getJobTitle());
    }

    /** Collect custom attributes keys for Autofill feature
     * @param Contact $c
     * @param $array
     */
    private function prepareAutofillCustoms(Contact &$c, &$array) {
        $customs = $c->getCustomAttributes();
        foreach ($customs as $cs)
            $this->addIfNotNull($array, $cs->getKey());
    }

}
<?php
namespace App\Components\Forms;

use App\Model\CustomAttribute;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

/** Class handles process of creating custom attributes of currently edit(created) Contact.
 * Form uses session storage and saves the attributes to it. Once the whole Contact form is submitted entries
 * are saved with the contact and session is cleared.
 */
class CustomAttributeFromFactory {
	use SmartObject;

    const SESSION_SECTION = "custom-attributes";
    const KEY_FIELD = "gd_custom_key";
    const VALUE_FIELD = "gd_custom_field";

    /** @var Session */
    private $session;

    /**
     * CustomAttributeFromFactory constructor.
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->session= $session;
    }

    /** Create the form
     * @return Form
     */
    public function createAddCustomAttributeForm() {
        $f = new Form(null, "addCustomAttributeForm");
        $f->addProtection("This form is protected against CSRF!");
        $f->elementPrototype->addAttributes(array('class' => 'ajax'));
        $f->addGroup("Custom attributes");
        $f->addText(self::KEY_FIELD)->setAttribute('placeholder', 'Attribute name')->setRequired();
        $f->addText(self::VALUE_FIELD)->setAttribute('placeholder', 'Attribute value')->setRequired();
        $f->addSubmit('add_custom_attribute','Add custom attribute')->getControlPrototype()->setAttribute("class","btn");
        $f->onValidate[] = array($this, 'addCustomAttributeFormValidation');
        $f->onSuccess[] = array($this, 'addCustomAttributeFormSuccess');
        return $f;
    }

    /** Validate the form.
     * There cannot be two custom attributes with the same key.
     * @param Form $form
     * @param ArrayHash $data
     */
    public function addCustomAttributeFormValidation(Form $form, ArrayHash $data) {
        if(($customs = self::getEntries($this->session)) != null) {
            /** @var CustomAttribute $storedAttr */
            foreach ($customs as $storedAttr)
                if($storedAttr->getKey() == $data[self::KEY_FIELD]) {
                    $form->addError("Key already used.");
                    return;
                }
        }
    }

    /** Save the form response to storage and clear the form
     * @param Form $form
     * @param ArrayHash $data
     */
    public function addCustomAttributeFormSuccess(Form $form, ArrayHash $data) {
        self::addEntry($this->session, new CustomAttribute($data[self::KEY_FIELD], $data[self::VALUE_FIELD]));
        $form[self::VALUE_FIELD]->setValue('');
        $form[self::KEY_FIELD]->setValue('');
    }

    /** Empty the storage session storage
     * @param Session $s
     */
    public static function releaseEntries(Session $s) {
        if(!$s->hasSection(self::SESSION_SECTION))
            return;
        unset($s->getSection(self::SESSION_SECTION)->customs);
    }

    /** Get all custom attributes from session storage
     * @param Session $s
     * @return CustomAttribute[]|null
     */
    public static function getEntries(Session $s) {
        if(!$s->hasSection(self::SESSION_SECTION))
            return null;
        return $s->getSection(self::SESSION_SECTION)->customs;
    }

    /** Remove single custom attribute from session storage
     * @param Session $s
     * @param $id
     */
    public static function removeEntry(Session $s, $id) {
        if(!$s->hasSection(self::SESSION_SECTION))
            return;
        unset($s->getSection(self::SESSION_SECTION)->customs[$id]);
    }

    /** Add custom attribute to the session storage
     * @param Session $s
     * @param CustomAttribute $atr
     */
    public static function addEntry(Session $s, CustomAttribute $atr) {
        $s->getSection(self::SESSION_SECTION)->customs[] = $atr;
    }
}
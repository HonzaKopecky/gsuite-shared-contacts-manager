<?php

namespace App\Model;

/** Class represents phone contact attribute
 */
class PhoneAttribute extends Attribute {
    const PHONE_HOME = "http://schemas.google.com/g/2005#home";
    const PHONE_MOBILE = "http://schemas.google.com/g/2005#mobile";
    const PHONE_FAX = "http://schemas.google.com/g/2005#fax";
    const PHONE_WORK = "http://schemas.google.com/g/2005#work";
    const ATR_NAME = "gd:phoneNumber";

    /** @var string */
    private $value;
    /** @var string */
    private $type;

    /**
     * PhoneAttribute constructor.
     * @param string $value
     * @param string $type
     */
    function __construct($value = null, $type = null) {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function fromXML(\SimpleXMLElement &$xml) {
        $this->value = (string)$xml;
        $this->type = (string)$xml["rel"];
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toXML(\SimpleXMLElement &$xml) {
        $phone = $xml->addChild(self::ATR_NAME, $this->value, ContactService::XMLNS_GD);
        $phone->addAttribute("rel", $this->type);
    }


}
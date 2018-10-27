<?php

namespace App\Model;

/** Class represents email contact attribute
 */
class EmailAttribute extends Attribute {
    const EMAIL_HOME = "http://schemas.google.com/g/2005#home";
    const EMAIL_WORK = "http://schemas.google.com/g/2005#work";
    const EMAIL_OTHER = "http://schemas.google.com/g/2005#other";
    const ATR_NAME = "gd:email";

    /** @var string */
    private $value;
    /** @var string */
    private $type;

    public function __construct($value = null, $type = null) {
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
    public function toXML(\SimpleXMLElement &$xml) {
        $email = $xml->addChild(EmailAttribute::ATR_NAME, null, ContactService::XMLNS_GD);
        $email->addAttribute("address", $this->value);
        $email->addAttribute("rel", $this->type);
    }

    /**
     * @inheritdoc
     */
    public function fromXML(\SimpleXMLElement &$xml) {
        $this->value = (string)$xml["address"];
        $this->type = (string)$xml["rel"];
        return $this;
    }
}
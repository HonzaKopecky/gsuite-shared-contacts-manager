<?php

namespace App\Model;

/** Class represents custom attribute (key, value pairs)
 */
class CustomAttribute extends Attribute {
    const ATR_NAME = "gd:extendedProperty";
    const MAXIMUM_AMOUNT = 10;

    /** @var string */
	private $key;
	/** @var string */
	private $value;

	public function __construct($key = null, $value = null) {
        $this->setKey($key);
        $this->setValue($value);
	}

    /**
     * @inheritDoc
     */
    public function toXML(\SimpleXMLElement &$xml)
    {
        $custAttr = $xml->addChild(self::ATR_NAME, null, ContactService::XMLNS_GD);
        if($this->key != null)
            $custAttr->addAttribute('name', $this->key);
        if($this->value != null)
            $custAttr->addAttribute('value', $this->value);
    }

    /**
     * @inheritDoc
     */
    public function fromXML(\SimpleXMLElement &$xml)
    {
        $this->key = (string)$xml["name"];
        $this->value = (string)$xml["value"];
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return CustomAttribute
     */
    public function setKey($key)
    {
        if($key == "")
            $key = null;
        $this->key = $key;
        return $this;
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
     * @return CustomAttribute
     */
    public function setValue($value)
    {
        if($value == "")
            $value = null;
        $this->value = $value;
        return $this;
    }




}
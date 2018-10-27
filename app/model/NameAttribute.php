<?php

namespace App\Model;

/** Class represent the name of Contact
 */
class NameAttribute extends Attribute {
    const ATR_NAME = "gd:name";
    const ATR_PREFIX_NAME = "gd:namePrefix";
    const ATR_GIVEN_NAME = "gd:givenName";
    const ATR_ADD_NAME = "gd:additionalName";
    const ATR_FAMILY_NAME = "gd:familyName";
    const ATR_SUFFIX_NAME = "gd:nameSuffix";

    /** @var string */
    private $givenName;
    /** @var string */
    private $additionalName;
    /** @var string */
    private $familyName;
    /** @var string */
    private $prefix;
    /** @var string */
    private $suffix;

    /**
     * @return string
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * @param string $givenName
     */
    public function setGivenName($givenName)
    {
        if($givenName == "")
            $givenName = null;
        $this->givenName = $givenName;
    }

    /**
     * @return string
     */
    public function getAdditionalName()
    {
        return $this->additionalName;
    }

    /**
     * @param string $additionalName
     */
    public function setAdditionalName($additionalName)
    {
        if($additionalName == "")
            $additionalName = null;
        $this->additionalName = $additionalName;
    }

    /**
     * @return string
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * @param string $familyName
     */
    public function setFamilyName($familyName)
    {
        if($familyName == "")
            $familyName = null;
        $this->familyName = $familyName;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        if($prefix == "")
            $prefix = null;
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     */
    public function setSuffix($suffix)
    {
        if($suffix == "")
            $suffix = null;
        $this->suffix = $suffix;
    }

    /**
     * @return string
     */
    public function getFullName() {
        return str_replace('  ', ' ', "$this->prefix $this->givenName $this->additionalName $this->familyName $this->suffix");
    }

    /**
     * @return string
     */
    public function getName() {
        return "$this->givenName $this->familyName";
    }

    /**
     * @inheritdoc
     */
    public function toXML(\SimpleXMLElement &$xml) {
        $nameAttr = $xml->addChild(NameAttribute::ATR_NAME, null, ContactService::XMLNS_GD);
        if($this->prefix != null)
            $nameAttr->addChild(NameAttribute::ATR_PREFIX_NAME, $this->prefix);
        if($this->givenName != null)
            $nameAttr->addChild(NameAttribute::ATR_GIVEN_NAME, $this->givenName);
        if($this->additionalName != null)
            $nameAttr->addChild(NameAttribute::ATR_ADD_NAME, $this->additionalName);
        if($this->familyName != null)
            $nameAttr->addChild(NameAttribute::ATR_FAMILY_NAME, $this->familyName);
        if($this->suffix != null)
            $nameAttr->addChild(NameAttribute::ATR_SUFFIX_NAME, $this->suffix);
    }

    /**
     * @inheritdoc
     */
    public function fromXML(\SimpleXMLElement &$xml) {
        $this->prefix = self::getChildValue($xml, NameAttribute::ATR_PREFIX_NAME);
        $this->givenName = self::getChildValue($xml, NameAttribute::ATR_GIVEN_NAME);
        $this->additionalName = self::getChildValue($xml, NameAttribute::ATR_ADD_NAME);
        $this->familyName = self::getChildValue($xml, NameAttribute::ATR_FAMILY_NAME);
        $this->suffix = self::getChildValue($xml, NameAttribute::ATR_SUFFIX_NAME);
        return $this;
    }

}
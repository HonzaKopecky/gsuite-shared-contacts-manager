<?php

namespace App\Model;

/** Class represent the address of Contact
 */
class AddressAttribute extends Attribute
{
    const ATR_NAME = "gd:structuredPostalAddress";
    const ATR_CITY = "gd:city";
    const ATR_STREET = "gd:street";
    const ATR_REGION = "gd:region";
    const ATR_COUNTRY = "gd:country";
    const ATR_POSTCODE = "gd:postcode";

    /** @var string */
    private $street;
    /** @var string */
    private $city;
    /** @var string */
    private $region;
    /** @var string */
    private $country;
    /** @var string */
    private $postCode;

    /**
     * @inheritDoc
     */
    public function toXML(\SimpleXMLElement &$xml)
    {
        $addrAttr = $xml->addChild(AddressAttribute::ATR_NAME, null, ContactService::XMLNS_GD);
        $addrAttr->addAttribute('rel','http://schemas.google.com/g/2005#work');
        $addrAttr->addAttribute('primary','true');
        if($this->city != null)
            $addrAttr->addChild(AddressAttribute::ATR_CITY, $this->city);
        if($this->street != null)
            $addrAttr->addChild(AddressAttribute::ATR_STREET, $this->street);
        if($this->region != null)
            $addrAttr->addChild(AddressAttribute::ATR_REGION, $this->region);
        if($this->country != null)
            $addrAttr->addChild(AddressAttribute::ATR_COUNTRY, $this->country);
        if($this->postCode != null)
            $addrAttr->addChild(AddressAttribute::ATR_POSTCODE, $this->postCode);
    }

    /**
     * @inheritDoc
     */
    public function fromXML(\SimpleXMLElement &$xml)
    {
        $this->street = self::getChildValue($xml, AddressAttribute::ATR_STREET);
        $this->city = self::getChildValue($xml, AddressAttribute::ATR_CITY);
        $this->region = self::getChildValue($xml, AddressAttribute::ATR_REGION);
        $this->country = self::getChildValue($xml, AddressAttribute::ATR_COUNTRY);
        $this->postCode = self::getChildValue($xml, AddressAttribute::ATR_POSTCODE);
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return AddressAttribute
     */
    public function setStreet($street)
    {
        if($street == "")
            $street = null;
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return AddressAttribute
     */
    public function setCity($city)
    {
        if($city == "")
            $city = null;
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     * @return AddressAttribute
     */
    public function setRegion($region)
    {
        if($region == "")
            $region = null;
        $this->region = $region;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return AddressAttribute
     */
    public function setCountry($country)
    {
        if($country == "")
            $country = null;
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @param string $postCode
     * @return AddressAttribute
     */
    public function setPostCode($postCode)
    {
        if($postCode == "")
            $postCode = null;
        $this->postCode = $postCode;
        return $this;
    }


}
<?php

namespace App\Tests\Units;

require_once '../bootstrap.php';

use App\Model\ContactService;

class AddressAttributeTest extends \Tester\TestCase
{
    public function prepareAttribute() {
        $atr = new \App\Model\AddressAttribute();
        $atr->setCity("Mesto");
        $atr->setCountry("zeme");
        $atr->setPostCode("25112");
        $atr->setRegion("kraj");
        $atr->setStreet("ulice");
        return $atr;
    }

    public function testXMLConversion() {
        $atr = $this->prepareAttribute();
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><atom:entry xmlns:atom="'.ContactService::XMLNS_ATOM.'" xmlns:gd="'.ContactService::XMLNS_GD.'"></atom:entry>');
        $atr->toXML($xml);
        $atrNew = new \App\Model\AddressAttribute();
        $atrNew->fromXML($xml);
        \Tester\Assert::equal($atr->getCity(), $atrNew->getCity());
        \Tester\Assert::equal($atr->getCountry(), $atrNew->getCountry());
        \Tester\Assert::equal($atr->getPostCode(), $atrNew->getPostCode());
        \Tester\Assert::equal($atr->getRegion(), $atrNew->getRegion());
        \Tester\Assert::equal($atr->getStreet(), $atrNew->getStreet());
    }
}

(new AddressAttributeTest())->run();
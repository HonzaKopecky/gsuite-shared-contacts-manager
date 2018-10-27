<?php

namespace App\Tests\Units;

require_once '../bootstrap.php';

use App\Model\ContactService;

class OrganizationAttributeTest extends \Tester\TestCase
{
    public function prepareAttribute() {
        $atr = new \App\Model\OrganizationAttribute();
        $atr->setJobTitle("title");
        $atr->setJobDescription("description");
        $atr->setDepartment("department");
        $atr->setName("name");
        return $atr;
    }

    public function testXMLConversion() {
        $atr = $this->prepareAttribute();
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><atom:entry xmlns:atom="'.ContactService::XMLNS_ATOM.'" xmlns:gd="'.ContactService::XMLNS_GD.'"></atom:entry>');
        $atr->toXML($xml);
        $atrNew = new \App\Model\OrganizationAttribute();
        $atrNew->fromXML($xml);
        \Tester\Assert::equal($atr->getJobTitle(), $atrNew->getJobTitle());
        \Tester\Assert::equal($atr->getJobDescription(), $atrNew->getJobDescription());
        \Tester\Assert::equal($atr->getDepartment(), $atrNew->getDepartment());
        \Tester\Assert::equal($atr->getName(), $atrNew->getName());
    }
}

(new OrganizationAttributeTest())->run();
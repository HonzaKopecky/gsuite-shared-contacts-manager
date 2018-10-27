<?php

namespace App\Model;

/** Class represents the organization attribute of a Contact
 */
class OrganizationAttribute extends Attribute {
    const ATR_NAME = 'gd:organization';
    const ATR_ORG_NAME = 'gd:orgName';
    const ATR_DEPARTMENT = 'gd:orgDepartment';
    const ATR_TITLE = 'gd:orgTitle';
    const ATR_DESCRIPTION = 'gd:orgJobDescription';

    /** @var string */
    private $name;
    /** @var string */
    private $department;
    /** @var string */
    private $jobTitle;
    /** @var string */
    private $jobDescription;

    /** @inheritdoc */
    public function toXML(\SimpleXMLElement &$xml)
    {
        $orgAttr = $xml->addChild(OrganizationAttribute::ATR_NAME, null, ContactService::XMLNS_GD);
        $orgAttr->addAttribute('rel','http://schemas.google.com/g/2005#work');
        if($this->name != null)
            $orgAttr->addChild(OrganizationAttribute::ATR_ORG_NAME, $this->name);
        if($this->department != null)
            $orgAttr->addChild(OrganizationAttribute::ATR_DEPARTMENT, $this->department);
        if($this->jobTitle != null)
            $orgAttr->addChild(OrganizationAttribute::ATR_TITLE, $this->jobTitle);
        if($this->jobDescription != null)
            $orgAttr->addChild(OrganizationAttribute::ATR_DESCRIPTION, $this->jobDescription);
    }

    /** @inheritdoc */
    public function fromXML(\SimpleXMLElement &$xml)
    {
        $this->name = self::getChildValue($xml, OrganizationAttribute::ATR_ORG_NAME);
        $this->department = self::getChildValue($xml, OrganizationAttribute::ATR_DEPARTMENT);
        $this->jobTitle = self::getChildValue($xml, OrganizationAttribute::ATR_TITLE);
        $this->jobDescription = self::getChildValue($xml, OrganizationAttribute::ATR_DESCRIPTION);
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return OrganizationAttribute
     */
    public function setName($name)
    {
        if($name == "")
            $name = null;
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param string $department
     * @return OrganizationAttribute
     */
    public function setDepartment($department)
    {
        if($department == "")
            $department = null;
        $this->department = $department;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobTitle()
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     * @return OrganizationAttribute
     */
    public function setJobTitle($jobTitle)
    {
        if($jobTitle == "")
            $jobTitle = null;
        $this->jobTitle = $jobTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobDescription()
    {
        return $this->jobDescription;
    }

    /**
     * @param string $jobDescription
     * @return OrganizationAttribute
     */
    public function setJobDescription($jobDescription)
    {
        if($jobDescription=="")
            $jobDescription = null;
        $this->jobDescription = $jobDescription;
        return $this;
    }


}
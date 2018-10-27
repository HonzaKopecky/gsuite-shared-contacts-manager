<?php

namespace App\Model;

use Nette\InvalidArgumentException;
use Nette\SmartObject;
use Nette\OutOfRangeException;
use Nette\Utils\DateTime;

/** Class represents a Google Domain Shared Contact.
 * @property integer $id
 */
class Contact {
	use SmartObject;

	/** @var string
	 */
	private $id;

    /** @var NameAttribute */
	private $name;

    /** @var EmailAttribute[] */
    private $emails = array();

    /** @var PhoneAttribute[] */
    private $phones = array();

    /** @var  CustomAttribute[] */
    private $customAttributes = array();

    /** @var AddressAttribute */
    private $address;

    /** @var OrganizationAttribute */
    private $organization;

    /** @var string */
	private $editTarget;

	/** @var string */
	private $photoTarget;

	/** @var  SerializableImage */
	private $profilePhoto;

	/** @var DateTime */
	private $editTime;


    /**
     * @return Attribute[]
     */
    public function getAttributes() {
        $attributes = array();
        if($this->emails != null)
            $attributes = array_merge($attributes, $this->emails);
        if($this->phones != null)
            $attributes = array_merge($attributes, $this->phones);
        if($this->customAttributes != null)
            $attributes = array_merge($attributes, $this->customAttributes);
        if($this->name != null)
            $attributes[] = $this->name;
        if($this->address != null)
            $attributes[] = $this->address;
        if($this->organization != null)
            $attributes[] = $this->organization;
        return $attributes;
    }

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @return Contact
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

    /**
     * @return NameAttribute
     */
    public function getName() {
	    return $this->name;
    }

    /**
     * @param NameAttribute $n
     * @return Contact
     */
    public function setName(NameAttribute $n) {
	    $this->name = $n;
	    return $this;
    }

    /**
 * @return EmailAttribute[]
 */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param EmailAttribute $email
     * @return Contact
     */
    public function addEmail(EmailAttribute $email)
    {
        $this->emails[] = $email;
        return $this;
    }

    /**
     * @return PhoneAttribute[]
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * @param PhoneAttribute $phone
     * @return Contact
     */
    public function addPhone(PhoneAttribute $phone)
    {
        $this->phones[] = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getEditTarget()
    {
        return $this->editTarget;
    }

    /**
     * @param string $editTarget
     * @return Contact
     */
    public function setEditTarget($editTarget)
    {
        $this->editTarget = $editTarget;
        return $this;
    }

    /**
     * @return SerializableImage
     */
    public function getProfilePhoto()
    {
        return $this->profilePhoto;
    }

    /**
     * @param SerializableImage $profilePhoto
     * @return Contact
     */
    public function setProfilePhoto(SerializableImage $profilePhoto)
    {
        $this->profilePhoto = $profilePhoto;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhotoTarget()
    {
        return $this->photoTarget;
    }

    /**
     * @param string $photoTarget
     * @return Contact
     */
    public function setPhotoTarget($photoTarget)
    {
        $this->photoTarget = $photoTarget;
        return $this;
    }

    /**
     * @return AddressAttribute
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param AddressAttribute $address
     * @return Contact
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return OrganizationAttribute
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationAttribute $organization
     * @return Contact
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return CustomAttribute[]
     */
    public function getCustomAttributes()
    {
        return $this->customAttributes;
    }

    /**
     * @param CustomAttribute $attr
     * @return $this
     */
    public function addCustomAttribute(CustomAttribute $attr)
    {
        if(count($this->customAttributes) >= 10)
            throw new OutOfRangeException("Maximum of 10 custom attributes allowed for a contact!");
        $this->customAttributes[] = $attr;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEditTime()
    {
        return $this->editTime;
    }

    /**
     * @param DateTime $editTime
     * @return Contact
     */
    public function setEditTime($editTime)
    {
        $this->editTime = $editTime;
        return $this;
    }

}
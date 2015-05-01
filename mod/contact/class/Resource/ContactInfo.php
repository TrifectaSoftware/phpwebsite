<?php

namespace contact\Resource;

use contact\Resource\ContactInfo;

class ContactInfo extends \Resource
{
    /**
     * @var ContactInfo\PhysicalAddress
     */
    private $physical_address;

    /**
     * @var \Variable\String
     */
    private $phone_number;

    /**
     * @var \Variable\String
     */
    private $fax_number;

    /**
     * @var ContactInfo\Offsite
     */
    private $offsite;

    /**
     * @var ContactInfo\Map
     */
    private $map;

    /**
     * @var \Variable\Email
     */
    private $email;

    public function __construct()
    {
        $this->physical_address = new ContactInfo\PhysicalAddress;
        $this->phone_number = new \Variable\String(null, 'phone_number');
        $this->fax_number = new \Variable\String(null, 'phone_number');
        $this->fax_number->allowEmpty(true);
        $this->offsite = new ContactInfo\Offsite;
        $this->map = new ContactInfo\Map;
        $this->email = new \Variable\Email(null, 'email');
    }

    /**
     * 
     * @return contact\Resource\ContactInfo\PhysicalAddress
     */
    public function getPhysicalAddress()
    {
        return $this->physical_address;
    }

    /**
     * 
     * @return contact\Resource\ContactInfo\Offsite
     */
    public function getOffsite()
    {
        return $this->offsite;
    }

    /**
     * 
     * @return contact\Resource\ContactInfo\Map
     */
    public function getMap()
    {
        return $this->map;
    }

    public function getPhoneNumber($format = false)
    {
        $phone_number = $this->phone_number->get();
        if (!$format) {
            return $phone_number;
        }
        return '(' . substr($phone_number, 0, 3) . ') ' . substr($phone_number, 3, 3) . '-' . substr($phone_number, 6, 4);
    }

    public function setPhoneNumber($phone)
    {
        $phone = preg_replace('/[^\d]/', '', $phone);
        $this->phone_number->set($phone);
    }

    public function getFaxNumber($format = false)
    {
        $fax_number = $this->fax_number->get();
        if (!$format) {
            return $fax_number;
        }
        return '(' . substr($fax_number, 0, 3) . ') ' . substr($fax_number, 3, 3) . '-' . substr($fax_number, 6, 4);
    }

    public function setFaxNumber($fax)
    {
        $fax = preg_replace('/[^\d]/', '', $fax);
        $this->fax_number->set($fax);
    }

    public function setEmail($email)
    {
        $this->email->set($email);
    }

    public function getEmail()
    {
        $email = $this->email->get();
        return $email;
    }

}

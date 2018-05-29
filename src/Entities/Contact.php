<?php

namespace Entities;

use Entities\Traits\ReferenceTrait;

class Contact extends Base
{
    use ReferenceTrait;
    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $address1;

    /**
     * @var string
     */
    public $address2;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $zip;

    public function hydrate(array $contact)
    {
        $this->exchange($contact);
        $this->setReferenceId(sha1(json_encode($contact)));
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        if (!$this->firstname) {
            return '';
        }

        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        if (!$this->lastname) {
            return '';
        }

        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        if (!$this->phone) {
            return '';
        }

        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        if (!$this->email) {
            return '';
        }

        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getAddress1(): string
    {
        if (!$this->address1) {
            return '';
        }

        return $this->address1;
    }

    /**
     * @param string $address1
     */
    public function setAddress1(string $address1)
    {
        $this->address1 = $address1;
    }

    /**
     * @return string
     */
    public function getAddress2(): string
    {
        if (!$this->address2) {
            return '';
        }

        return $this->address2;
    }

    /**
     * @param null|string $address2
     */
    public function setAddress2(?string $address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        if (!$this->city) {
            return '';
        }

        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        if (!$this->state) {
            return '';
        }

        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        if (!$this->zip) {
            return '';
        }

        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(string $zip)
    {
        $this->zip = $zip;
    }
}

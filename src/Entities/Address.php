<?php
namespace Entities;

use Entities\Traits\ReferenceTrait;

class Address extends Base
{
    use ReferenceTrait;

    public $participant_id;

    public $firstname;

    public $lastname;

    public $address1;

    public $address2;

    public $city;

    public $state;

    public $zip;

    public $country = 840;

    public $country_code;

    public function hydrate(array $shipping)
    {
        $this->exchange($shipping);
        $this->setReferenceId(sha1(json_encode($shipping)));
    }

    /**
     * @return mixed
     */
    public function getParticipantId()
    {
        return $this->participant_id;
    }

    /**
     * @param mixed $id
     */
    public function setParticipantId($id)
    {
        $this->participant_id = $id;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return mixed
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param mixed $address1
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
    }

    /**
     * @return mixed
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param mixed $address2
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * ISO-3 country code
     * @param $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @param mixed $country_code
     */
    public function setCountryCode($country_code): void
    {
        $this->country_code = $country_code;
    }

}

<?php
namespace Entities;

use Entities\Traits\OrganizationTrait;
use Entities\Traits\ProgramTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

/**
 * Class Participant
 * @package Entities
 */
class Participant extends Base
{
    use StatusTrait;
    use TimestampTrait;
    use OrganizationTrait;
    use ProgramTrait;

    public $id;

    public $email_address;

    public $password;

    public $unique_id;

    public $sso;

    public $credit;

    public $firstname;

    public $lastname;

    public $address_reference;

    public $phone;

    public $birthdate;

    /**
     * @var ParticipantMeta[]
     */
    private $meta;

    /**
     * @var Address|null
     */
    private $address;

    public $deactivated_at;


    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * @param mixed $email_address
     */
    public function setEmailAddress($email_address)
    {
        $this->email_address = $email_address;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = null;

        if ($password !== "") {
            $password = password_hash($password, PASSWORD_BCRYPT);
            $this->password = $password;
        }
    }

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * @param mixed $unique_id
     */
    public function setUniqueId($unique_id)
    {
        $this->unique_id = $unique_id;
    }

    public function generateSsoToken()
    {
        if ($this->sso === null) {
            $tokenString = time() . $this->getProgramId() . $this->getUniqueId();
            $this->sso = hash_hmac('sha512', $tokenString, $this->getUniqueId());
        }

        return $this->sso;
    }

    /**
     * @return string|null
     */
    public function getSso()
    {
        return $this->sso;
    }

    /**
     * @param string $sso
     */
    public function setSso($sso)
    {
        $this->sso = $sso;
    }

    /**
     * @return mixed
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @return mixed
     */
    public function getCreditInDollars()
    {
        return bcdiv($this->credit, $this->getProgram()->getPoint(), 5);
    }

    public function getFormattedCredit()
    {
        return number_format(
            $this->credit,
            0,
            '.',
            ','
        );
    }

    /**
     * @param mixed $credit
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;
    }

    public function subtractCredit($amount)
    {
        $credit = bcsub($this->credit, $amount, 2);
        $this->setCredit($credit);
    }

    public function addCredit($amount)
    {
        $credit = bcadd($this->credit, $amount, 2);
        $this->setCredit($credit);
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        if ($this->firstname === null) {
            return '';
        }

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
        if ($this->lastname === null) {
            return '';
        }

        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getName()
    {
        return implode(" ", [$this->firstname, $this->lastname]);
    }

    /**
     * @return mixed
     */
    public function getAddressReference()
    {
        return $this->address_reference;
    }

    /**
     * @param mixed $reference
     */
    public function setAddressReference($reference)
    {
        $this->address_reference = $reference;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param mixed $birthdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    public function getFormattedBirthdate()
    {
        $date = '';

        if ($this->birthdate !== null) {
            $date = new \DateTime;
            $timestamp = strtotime($this->birthdate);
            $date->setTimestamp($timestamp);
            $date = $date->format('Y-m-d');
        }

        return $date;
    }

    /**
     * @return mixed
     */
    public function getAddress():?Address
    {
        //@TODO implement fetching of reference
        return $this->address;
    }

    /**
     * @param mixed $anAddress
     */
    public function setAddress(array $anAddress)
    {
        $address = new Address();
        $address->hydrate($anAddress);
        $address->setParticipantId($this->getId());
        $this->address = $address;
        $this->setAddressReference($address->getReferenceId());
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param mixed $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }


    /**
     * @return mixed
     */
    public function getDeactivatedAt()
    {
        return $this->deactivated_at;
    }

    /**
     * @param mixed $time
     */
    public function setDeactivatedAt($time)
    {
        $this->deactivated_at = $time;
    }

}

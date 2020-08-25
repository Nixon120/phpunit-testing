<?php
namespace Entities;

use Entities\Traits\OrganizationTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

/**
 * Class User
 * @package Entities
 */
class User extends \Entities\Base
{
    use StatusTrait;
    use TimestampTrait;
    use OrganizationTrait;

    public $id;

    public $email_address;

    public $password;

    public $firstname;

    public $lastname;

    public $role;

    public $invite_token;

    private $organizationOwnershipIdentificationCollection = [];

    private $programOwnershipIdentificationCollection = [];

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

    public function getName()
    {
        return implode(" ", [$this->firstname, $this->lastname]);
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return mixed
     */
    public function getInviteToken()
    {
        return $this->invite_token;
    }

    /**
     * @param mixed $invite_token
     */
    public function setInviteToken($invite_token)
    {
        $this->invite_token = $invite_token;
    }

    /**
     * @return array
     */
    public function getOrganizationOwnershipIdentificationCollection(): array
    {
        return $this->organizationOwnershipIdentificationCollection;
    }

    /**
     * @param array $organizationOwnershipIdentificationCollection
     */
    public function setOrganizationOwnershipIdentificationCollection(array $organizationOwnershipIdentificationCollection)
    {
        $this->organizationOwnershipIdentificationCollection = $organizationOwnershipIdentificationCollection;
    }

    /**
     * @return mixed
     */
    public function getProgramOwnershipIdentificationCollection(): array
    {
        return $this->programOwnershipIdentificationCollection;
    }

    public function setProgramOwnershipIdentificationCollection(array $programOwnershipIdentificationCollection)
    {
        $this->programOwnershipIdentificationCollection = $programOwnershipIdentificationCollection;
    }

    public function toArray()
    {
        $data = parent::toArray();
        $organization = $this->getOrganization();
        $data['organization_id'] = $organization !== null ? $organization->getId() : null;

        return $data;
    }
}

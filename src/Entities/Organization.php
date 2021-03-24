<?php

namespace Entities;

use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class Organization extends Base
{
    use StatusTrait;
    use TimestampTrait;

    public $name;

    public $unique_id;

    public $parent_id;

    private $parent;

    private $domains = [];

    public $lvl;

    public $lft;

    public $rgt;

    public $industry_program;

    public $accounts_payable_contact_reference;

    /**
     * @var Contact
     */
    private $accounts_payable_contact;

    public $company_contact_reference;

    /**
     * @var Contact
     */
    private $company_contact;

    private $program_count;

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
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * @param mixed $id
     */
    public function setUniqueId($id)
    {
        $this->unique_id = $id;
    }

    /**
     * @return mixed
     */
    public function getParentId():?string
    {
        if (is_null($this->parent_id)) {
            return null;
        }

        return $this->parent_id;
    }

    /**
     * @param mixed $id
     */
    public function setParentId(?string $id)
    {
        $this->parent_id = $id;
    }

    /**
     * @return Organization|null
     */
    public function getParent():?Organization
    {
        return $this->parent;
    }

    /**
     * @param Organization|null $parent
     */
    public function setParent(?Organization $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Domain[]
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * @param array $domains
     */
    public function setDomains(array $domains = [])
    {
        $this->domains = $domains;
    }

    /**
     * @return mixed
     */
    public function getLft():?string
    {
        return $this->lft;
    }

    /**
     * @param mixed $left
     */
    public function setLft(?string $left)
    {
        $this->lft = $left;
    }

    /**
     * @return mixed
     */
    public function getRgt():?string
    {
        return $this->rgt;
    }

    /**
     * @param mixed $right
     */
    public function setRgt(?string $right)
    {
        $this->rgt = $right;
    }

    /**
     * @return mixed
     */
    public function getLvl():?string
    {
        return $this->lvl;
    }

    /**
     * @param mixed $level
     */
    public function setLvl(?string $level)
    {
        $this->rgt = $level;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getProgramCount()
    {
        return $this->program_count;
    }

    public function setProgramCount($programCount)
    {
        $this->program_count = $programCount;
    }

    /**
     * @return mixed
     */
    public function getAccountsPayableContactReference()
    {
        return $this->accounts_payable_contact_reference;
    }

    /**
     * @param mixed $accounts_payable_contact_reference
     */
    public function setAccountsPayableContactReference($accounts_payable_contact_reference)
    {
        $this->accounts_payable_contact_reference = $accounts_payable_contact_reference;
    }

    public function hasCompanyContact()
    {
        if ($this->company_contact instanceof Contact) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getCompanyContactReference()
    {
        return $this->company_contact_reference;
    }

    /**
     * @param mixed $company_contact_reference
     */
    public function setCompanyContactReference($company_contact_reference)
    {
        $this->company_contact_reference = $company_contact_reference;
    }

    public function hasAccountsPayableContact()
    {
        if ($this->accounts_payable_contact instanceof Contact) {
            return true;
        }

        return false;
    }

    /**
     * @return Contact
     */
    public function getAccountsPayableContact(): Contact
    {
        if (!$this->accounts_payable_contact) {
            return new Contact();
        }

        return $this->accounts_payable_contact;
    }

    /**
     * @param Contact $accounts_payable_contact
     */
    public function setAccountsPayableContact(Contact $accounts_payable_contact)
    {
        $this->accounts_payable_contact = $accounts_payable_contact;
    }

    /**
     * @return Contact
     */
    public function getCompanyContact(): Contact
    {
        if (!$this->company_contact) {
            return new Contact();
        }

        return $this->company_contact;
    }

    /**
     * @param Contact $company_contact
     */
    public function setCompanyContact(Contact $company_contact)
    {
        $this->company_contact = $company_contact;
    }

    /**
     * @return mixed
     */
    public function getIndustryProgram()
    {
        return $this->industry_program;
    }

    /**
     * @param mixed $industry_program
     */
    public function setIndustryProgram($industry_program): void
    {
        $this->industry_program = $industry_program;
    }
}

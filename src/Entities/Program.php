<?php

namespace Entities;

use Entities\Traits\OrganizationTrait;
use Entities\Traits\StatusTrait;
use DateTime;

class Program extends Base
{
    use OrganizationTrait;
    use StatusTrait;

    public $unique_id;

    public $name;

    public $role;

    public $point;

    public $phone;

    public $address1;

    public $address2;

    public $city;

    public $state;

    public $zip;

    public $url;

    public $domain_id;

    private $domain;

    public $published = 0;

    public $collect_ssn = 0;

    public $start_date;

    public $end_date;

    public $grace_period;

    public $timezone = 'America/Phoenix';

    /** @var AutoRedemption */
    private $autoRedemption;

    /** @var OneTimeAutoRedemption[] */
    private $oneTimeAutoRedemptions;

    /** @var ProductCriteria */
    private $productCriteria;

    /** @var  Sweepstake */
    private $sweepstake;

    /**
     * @var LayoutRow[]
     */
    private $layoutRows;

    /**
     * @var FeaturedProduct[]
     */
    private $featuredProducts;

    /**
     * @var array
     */
    public $meta;

    public $contact_reference;

    public $accounting_contact_reference;

    public $cost_center_id;

    public $invoice_to;

    public $deposit_amount = 0;

    public $low_level_deposit = 0;

    public $issue_1099 = 0;

    public $employee_payroll_file = 0;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var Contact
     */
    private $accountingContact;

    /**
     * @var ProgramType[]
     */
    private $programTypes;

    /**
     * @var array
     */
    private $actions;

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

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return mixed
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param mixed $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
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
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param mixed $address
     */
    public function setAddress1($address)
    {
        $this->address1 = $address;
    }

    /**
     * @return mixed
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param mixed $address
     */
    public function setAddress2($address)
    {
        $this->address2 = $address;
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getDomainId()
    {
        return $this->domain_id;
    }

    /**
     * @param mixed $domain
     */
    public function setDomainId($domain)
    {
        $this->domain_id = $domain;
    }

    /**
     * @return mixed
     */
    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    /**
     * @param ?Domain $domain
     */
    public function setDomain(?Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param string
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
        if ($start_date) {
            $start_date = new \DateTime($start_date);
            $this->start_date = $start_date->format('Y-m-d');
        }
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param string
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
        if ($end_date) {
            $end_date = new \DateTime($end_date);
            $this->end_date = $end_date->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->end_date === null) {
            return false;
        }
        $gracePeriod = $this->grace_period ?? 0;
        $timezone = $this->getTimezone() ?? 'America/Phoenix';
        $endDate = new DateTime($this->end_date, new \DateTimeZone("UTC"));
        $endDate->setTimezone(new \DateTimeZone($timezone));
        $endDate->modify("+$gracePeriod day");
        $endOfDayToday = date("Y-m-d", strtotime("now")) . ' 23:59:59';
        $endOfDayToday = new DateTime($endOfDayToday);

        return $endOfDayToday > $endDate;
    }

    /**
     * @return bool
     */
    public function isActiveAndNotExpired(): bool
    {
        return $this->isActive() === true && $this->isExpired() === false;
    }

    /**
     * @return string
     */
    public function getGracePeriod()
    {
        return $this->grace_period;
    }

    /**
     * @param string
     */
    public function setGracePeriod($grace_period)
    {
        $this->grace_period = $grace_period;
    }

    /**
     * @return string
     */
    public function getTimezone():?string
    {
        return $this->timezone;
    }

    /**
     * @return boolean
     */
    public function isValidTimezoneId($timezoneId = null) 
    {
        try {
            $tempZone = new \DateTimeZone($timezoneId);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function isPublished(): bool
    {
        return $this->published == 1;
    }

    public function collectSsn(): bool
    {
        return $this->collect_ssn == 1;
    }

    /**
     * @return int
     */
    public function getCollectSsn(): int
    {
        return $this->collect_ssn;
    }

    /**
     * @param int $collect_ssn
     */
    public function setCollectSsn(int $collect_ssn): void
    {
        $this->collect_ssn = $collect_ssn;
    }

    /**
     * @param mixed $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return mixed
     */
    public function getPublished()
    {
        return $this->published;
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
     * @TODO remove meta from program
     */
    public function setMeta(?array $meta = [])
    {
        $this->meta = $meta;
    }

    public function setAutoRedemption(?AutoRedemption $settings)
    {
        $this->autoRedemption = $settings;
    }

    public function getAutoRedemption(): ?AutoRedemption
    {
        return $this->autoRedemption;
    }

    /**
     * @return OneTimeAutoRedemption[]
     */
    public function getOneTimeAutoRedemptions(): array
    {
        if ($this->oneTimeAutoRedemptions === null) {
            $this->oneTimeAutoRedemptions = [];
        }

        return $this->oneTimeAutoRedemptions;
    }

    /**
     * @param array|null $oneTimeAutoRedemptions
     */
    public function setOneTimeAutoRedemptions(?array $oneTimeAutoRedemptions = null)
    {
        $this->oneTimeAutoRedemptions = $oneTimeAutoRedemptions;
    }

    public function setProductCriteria(?ProductCriteria $criteria)
    {
        $this->productCriteria = $criteria;
    }

    public function getProductCriteria(): ProductCriteria
    {
        if ($this->productCriteria === null) {
            $this->productCriteria = new ProductCriteria;
        }

        return $this->productCriteria;
    }

    public function setSweepstake(?Sweepstake $sweepstake)
    {
        $this->sweepstake = $sweepstake;
    }

    public function getSweepstake(): ?Sweepstake
    {
        if ($this->sweepstake === null) {
            $this->sweepstake = new Sweepstake;
        }

        return $this->sweepstake;
    }

    public function setLayoutRows(?array $layout = null)
    {
        $this->layoutRows = $layout;
    }

    /**
     * @return LayoutRow[]
     */
    public function getLayoutRows(): array
    {
        if ($this->layoutRows === null) {
            $this->layoutRows = [];
        }

        return $this->layoutRows;
    }

    public function setFeaturedProducts(?array $products = null)
    {
        $this->featuredProducts = $products;
    }

    /**
     * @return FeaturedProduct[]
     */
    public function getFeaturedProducts(): array
    {
        if ($this->featuredProducts === null) {
            $this->featuredProducts = [];
        }

        return $this->featuredProducts;
    }

    /**
     * @return mixed
     */
    public function getContactReference()
    {
        return $this->contact_reference;
    }

    /**
     * @param mixed $contact
     */
    public function setContactReference($contact)
    {
        $this->contact_reference = $contact;
    }

    public function hasContact()
    {
        if ($this->contact instanceof Contact) {
            return true;
        }

        return false;
    }

    /**
     * @return Contact
     */
    public function getContact(): Contact
    {
        if (!$this->contact) {
            return new Contact();
        }

        return $this->contact;
    }

    /**
     * @param Contact $contact
     */
    public function setContact(?Contact $contact = null)
    {
        if ($contact !== null) {
            $this->setContactReference($contact->getReferenceId());
        }

        $this->contact = $contact;
    }

    /**
     * @return mixed
     */
    public function getAccountingContactReference()
    {
        return $this->accounting_contact_reference;
    }

    /**
     * @param mixed $accounting_contact_reference
     */
    public function setAccountingContactReference($accounting_contact_reference)
    {
        $this->accounting_contact_reference = $accounting_contact_reference;
    }

    public function hasAccountingContact()
    {
        if ($this->accountingContact instanceof Contact) {
            return true;
        }

        return false;
    }

    /**
     * @return Contact
     */
    public function getAccountingContact()
    {
        if (!$this->accountingContact) {
            return new Contact();
        }

        return $this->accountingContact;
    }

    /**
     * @param Contact $accountingContact
     */
    public function setAccountingContact($accountingContact)
    {
        if ($accountingContact !== null) {
            $this->setAccountingContactReference($accountingContact->getReferenceId());
        }

        $this->accountingContact = $accountingContact;
    }

    /**
     * @return mixed
     */
    public function getInvoiceTo()
    {
        return $this->invoice_to;
    }

    /**
     * @param mixed $invoice_to
     */
    public function setInvoiceTo($invoice_to)
    {
        $this->invoice_to = $invoice_to;
    }

    public function getInvoiceToOptions(): array
    {
        return [
            'Top Level Client',
            'Parent Employer',
            'Employer'
        ];
    }

    /**
     * @return mixed
     */
    public function getDepositAmount(): int
    {
        if (is_null($this->deposit_amount)) {
            return 0;
        }

        return $this->deposit_amount;
    }

    /**
     * @param int $deposit_amount
     */
    public function setDepositAmount(int $deposit_amount)
    {
        $this->deposit_amount = $deposit_amount;

        if (empty($deposit_amount)) {
            $this->deposit_amount = 0;
        }
    }

    /**
     * @return mixed
     */
    public function getLowLevelDeposit(): int
    {
        if (is_null($this->low_level_deposit)) {
            return 0;
        }

        return $this->low_level_deposit;
    }

    /**
     * @param int $low_level_deposit
     */
    public function setLowLevelDeposit(int $low_level_deposit)
    {
        $this->low_level_deposit = $low_level_deposit;

        if (empty($low_level_deposit)) {
            $this->low_level_deposit = 0;
        }
    }

    /**
     * @return bool
     */
    public function getIssue1099(): bool
    {
        if ($this->issue_1099 === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param string|bool $issue_1099
     */
    public function setIssue1099($issue_1099)
    {
        if (in_array($issue_1099, ['yes', true], true)) {
            $this->issue_1099 = 1;
            return;
        }

        $this->issue_1099 = 0;
    }

    /**
     * @return mixed
     */
    public function getEmployeePayrollFile(): bool
    {
        if ($this->employee_payroll_file === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param string|bool $employee_payroll_file
     */
    public function setEmployeePayrollFile($employee_payroll_file)
    {
        if (in_array($employee_payroll_file, ["yes", true], true)) {
            $this->employee_payroll_file = 1;
            return;
        }

        $this->employee_payroll_file = 0;
    }

    /**
     * @return string
     */
    public function getCostCenterId(): string
    {
        if (is_null($this->cost_center_id)) {
            return '';
        }

        return $this->cost_center_id;
    }

    /**
     * @param string $cost_center_id
     */
    public function setCostCenterId(string $cost_center_id)
    {
        $this->cost_center_id = $cost_center_id;
    }

    /**
     * @return ProgramType[]|null
     */
    public function getProgramTypes(): ?array
    {
        return $this->programTypes;
    }

    /**
     * @param ProgramType[] $programTypes
     */
    public function setProgramTypes(array $programTypes): void
    {
        $this->programTypes = $programTypes;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        $actionCollection = [];
        foreach ($this->getProgramTypes() as $type) {
            foreach ($type->getActions() as $action => $boolean) {
                if (isset($actionCollection[$action]) === true
                    && $actionCollection[$action] === true
                    && $boolean === false) {
                    // if we've already set it as true, that means it's available and shouldn't be revoked
                    continue;
                }

                $actionCollection[$action] = $boolean;
            }
        }

        return $actionCollection;
    }

    /**
     * @param array $actions
     */
    public function setActions($actions): void
    {
        $this->actions = $actions;
    }
}

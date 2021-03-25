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

    /**
     * @var timeZones
     */
    private $timeZones = array ( 
        'America' => array (
            'America/Adak' => 'Adak -10:00',
            'America/Atka' => 'Atka -10:00',
            'America/Anchorage' => 'Anchorage -9:00',
            'America/Juneau' => 'Juneau -9:00',
            'America/Nome' => 'Nome -9:00',
            'America/Yakutat' => 'Yakutat -9:00',
            'America/Dawson' => 'Dawson -8:00',
            'America/Ensenada' => 'Ensenada -8:00',
            'America/Los_Angeles' => 'Los_Angeles -8:00',
            'America/Tijuana' => 'Tijuana -8:00',
            'America/Vancouver' => 'Vancouver -8:00',
            'America/Whitehorse' => 'Whitehorse -8:00',
            'America/Boise' => 'Boise -7:00',
            'America/Cambridge_Bay' => 'Cambridge_Bay -7:00',
            'America/Chihuahua' => 'Chihuahua -7:00',
            'America/Dawson_Creek' => 'Dawson_Creek -7:00',
            'America/Denver' => 'Denver -7:00',
            'America/Edmonton' => 'Edmonton -7:00',
            'America/Hermosillo' => 'Hermosillo -7:00',
            'America/Inuvik' => 'Inuvik -7:00',
            'America/Mazatlan' => 'Mazatlan -7:00',
            'America/Phoenix' => 'Phoenix -7:00',
            'America/Shiprock' => 'Shiprock -7:00',
            'America/Yellowknife' => 'Yellowknife -7:00',
            'America/Belize' => 'Belize -6:00',
            'America/Cancun' => 'Cancun -6:00',
            'America/Chicago' => 'Chicago -6:00',
            'America/Costa_Rica' => 'Costa_Rica -6:00',
            'America/El_Salvador' => 'El_Salvador -6:00',
            'America/Guatemala' => 'Guatemala -6:00',
            'America/Knox_IN' => 'Knox_IN -6:00',
            'America/Managua' => 'Managua -6:00',
            'America/Menominee' => 'Menominee -6:00',
            'America/Merida' => 'Merida -6:00',
            'America/Mexico_City' => 'Mexico_City -6:00',
            'America/Monterrey' => 'Monterrey -6:00',
            'America/Rainy_River' => 'Rainy_River -6:00',
            'America/Rankin_Inlet' => 'Rankin_Inlet -6:00',
            'America/Regina' => 'Regina -6:00',
            'America/Swift_Current' => 'Swift_Current -6:00',
            'America/Tegucigalpa' => 'Tegucigalpa -6:00',
            'America/Winnipeg' => 'Winnipeg -6:00',
            'America/Atikokan' => 'Atikokan -5:00',
            'America/Bogota' => 'Bogota -5:00',
            'America/Cayman' => 'Cayman -5:00',
            'America/Coral_Harbour' => 'Coral_Harbour -5:00',
            'America/Detroit' => 'Detroit -5:00',
            'America/Fort_Wayne' => 'Fort_Wayne -5:00',
            'America/Grand_Turk' => 'Grand_Turk -5:00',
            'America/Guayaquil' => 'Guayaquil -5:00',
            'America/Havana' => 'Havana -5:00',
            'America/Indianapolis' => 'Indianapolis -5:00',
            'America/Iqaluit' => 'Iqaluit -5:00',
            'America/Jamaica' => 'Jamaica -5:00',
            'America/Lima' => 'Lima -5:00',
            'America/Louisville' => 'Louisville -5:00',
            'America/Montreal' => 'Montreal -5:00',
            'America/Nassau' => 'Nassau -5:00',
            'America/New_York' => 'New_York -5:00',
            'America/Nipigon' => 'Nipigon -5:00',
            'America/Panama' => 'Panama -5:00',
            'America/Pangnirtung' => 'Pangnirtung -5:00',
            'America/Port-au-Prince' => 'Port-au-Prince -5:00',
            'America/Resolute' => 'Resolute -5:00',
            'America/Thunder_Bay' => 'Thunder_Bay -5:00',
            'America/Toronto' => 'Toronto -5:00',
            'America/Caracas' => 'Caracas -4:-30',
            'America/Anguilla' => 'Anguilla -4:00',
            'America/Antigua' => 'Antigua -4:00',
            'America/Aruba' => 'Aruba -4:00',
            'America/Asuncion' => 'Asuncion -4:00',
            'America/Barbados' => 'Barbados -4:00',
            'America/Blanc-Sablon' => 'Blanc-Sablon -4:00',
            'America/Boa_Vista' => 'Boa_Vista -4:00',
            'America/Campo_Grande' => 'Campo_Grande -4:00',
            'America/Cuiaba' => 'Cuiaba -4:00',
            'America/Curacao' => 'Curacao -4:00',
            'America/Dominica' => 'Dominica -4:00',
            'America/Eirunepe' => 'Eirunepe -4:00',
            'America/Glace_Bay' => 'Glace_Bay -4:00',
            'America/Goose_Bay' => 'Goose_Bay -4:00',
            'America/Grenada' => 'Grenada -4:00',
            'America/Guadeloupe' => 'Guadeloupe -4:00',
            'America/Guyana' => 'Guyana -4:00',
            'America/Halifax' => 'Halifax -4:00',
            'America/La_Paz' => 'La_Paz -4:00',
            'America/Manaus' => 'Manaus -4:00',
            'America/Marigot' => 'Marigot -4:00',
            'America/Martinique' => 'Martinique -4:00',
            'America/Moncton' => 'Moncton -4:00',
            'America/Montserrat' => 'Montserrat -4:00',
            'America/Port_of_Spain' => 'Port_of_Spain -4:00',
            'America/Porto_Acre' => 'Porto_Acre -4:00',
            'America/Porto_Velho' => 'Porto_Velho -4:00',
            'America/Puerto_Rico' => 'Puerto_Rico -4:00',
            'America/Rio_Branco' => 'Rio_Branco -4:00',
            'America/Santiago' => 'Santiago -4:00',
            'America/Santo_Domingo' => 'Santo_Domingo -4:00',
            'America/St_Barthelemy' => 'St_Barthelemy -4:00',
            'America/St_Kitts' => 'St_Kitts -4:00',
            'America/St_Lucia' => 'St_Lucia -4:00',
            'America/St_Thomas' => 'St_Thomas -4:00',
            'America/St_Vincent' => 'St_Vincent -4:00',
            'America/Thule' => 'Thule -4:00',
            'America/Tortola' => 'Tortola -4:00',
            'America/Virgin' => 'Virgin -4:00',
            'America/St_Johns' => 'St_Johns -3:-30',
            'America/Araguaina' => 'Araguaina -3:00',
            'America/Bahia' => 'Bahia -3:00',
            'America/Belem' => 'Belem -3:00',
            'America/Buenos_Aires' => 'Buenos_Aires -3:00',
            'America/Catamarca' => 'Catamarca -3:00',
            'America/Cayenne' => 'Cayenne -3:00',
            'America/Cordoba' => 'Cordoba -3:00',
            'America/Fortaleza' => 'Fortaleza -3:00',
            'America/Godthab' => 'Godthab -3:00',
            'America/Jujuy' => 'Jujuy -3:00',
            'America/Maceio' => 'Maceio -3:00',
            'America/Mendoza' => 'Mendoza -3:00',
            'America/Miquelon' => 'Miquelon -3:00',
            'America/Montevideo' => 'Montevideo -3:00',
            'America/Paramaribo' => 'Paramaribo -3:00',
            'America/Recife' => 'Recife -3:00',
            'America/Rosario' => 'Rosario -3:00',
            'America/Santarem' => 'Santarem -3:00',
            'America/Sao_Paulo' => 'Sao_Paulo -3:00',
            'America/Noronha' => 'Noronha -2:00',
            'America/Scoresbysund' => 'Scoresbysund -1:00',
            'America/Danmarkshavn' => 'Danmarkshavn +0:00',
        )
    );

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

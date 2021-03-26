<?php
namespace Services\Program;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Interfaces as Interfaces;
use Controllers\Program\InputNormalizer;
use Entities\AutoRedemption;
use Entities\Contact;
use Entities\Domain;
use Entities\Event;
use Entities\Organization;
use Exception;
use Repositories\ContactRepository;
use Repositories\ProgramRepository;

class Program
{
    /**
     * @var ProgramRepository
     */
    public $repository;

    /**
     * @var ContactRepository
     */
    public $contactRepository;

    /**
     * @var \Entities\Program
     */
    private $program;

    /**
     * @var MessagePublisher
     */
    protected $eventPublisher;

    /**
     * @var timeZones
     */
    private $timeZones = [ 
        'America' => [ 
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
        ] 
    ];
    public function __construct(
        ProgramRepository $repository,
        ContactRepository $contactRepository,
        MessagePublisher $eventPublisher
    ) {
        $this->repository = $repository;
        $this->contactRepository = $contactRepository;
        $this->eventPublisher = $eventPublisher;
    }

    public function getSingle($id, $uniqueLookup = true): ?\Entities\Program
    {
        $program = $this->repository->getProgram($id, $uniqueLookup);
        if ($program) {
            return $program;
        }

        return null;
    }

    public function getAdjustments($input)
    {
        return $this->repository->getCreditAdjustmentsByMeta($input);
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new FilterNormalizer($input->getInput());
        $programs = $this->repository->getCollection($filter, $input->getPage(), $input->getLimit());
        return $programs;
    }

    public function isValidJson($json) {
        return json_decode(json_encode($json));
    }

    public function isValidDateFormat($jsonDate) {
        $tempDate = \DateTime::createFromFormat("Y-m-d H:i:s", $jsonDate);
        return $tempDate;
    }

    private function buildEntities($data): bool
    {
        if (!empty($data['organization'])) {
            $organization = $this->repository->getProgramOrganization($data['organization'], true);
            if (!$organization instanceof Organization) {
                $this->repository->setErrors([_('Organization not found')]);
                return false;
            }
            $data['organization_id'] = $organization->getId();
        }

        if (!empty($data['contact'])) {
            // Build the Company Contact entity.
            $contact = $this->program->getContact();
            $contact->hydrate($data['contact']);
            $this->program->setContact($contact);
            $reference = $this->program->getContactReference();
            $data['contact_reference'] = $reference;
        }

        if (!empty($data['accounting_contact'])) {
            // Build the Accounting Contact entity.
            $contact = $this->program->getAccountingContact();
            $contact->hydrate($data['accounting_contact']);
            $this->program->setAccountingContact($contact);
            $reference = $this->program->getAccountingContactReference();
            $data['accounting_contact_reference'] = $reference;
        }

        //unsetting unique_id here, can't change on update.
        if (!empty($data['auto_redemption'])) {
            $autoRedemption = new AutoRedemption;
            $autoRedemption->exchange($data['auto_redemption']);
            $autoRedemption->setAllParticipant(1); // This will be changed later.
            $this->program->setAutoRedemption($autoRedemption);
        }

        if (!empty($data['one_time_auto_redemptions'])) {
            $this->program->setOneTimeAutoRedemptions($data['one_time_auto_redemptions']);
        }

        if (!empty($data['url']) && $this->isUrlValid($data['url']) === true) {
            $url = explode('.', $data['url']);
            $subdomain = $url[0];
            array_shift($url);
            $domainString = implode('.', $url);
            $domain = $this->repository->getProgramDomainByDomainName($domainString);
            if (!$domain instanceof Domain) {
                $this->repository->setErrors([_('Domain not found for: ' . $domainString)]);
                return false;
            }
            $data['url'] = $subdomain;
            $data['domain_id'] = $domain->getId();
        }

        if (!empty($data['programTypes'])) {
            $collection = [];
            foreach ($data['programTypes'] as $programType) {
                $type = new \Entities\ProgramType;
                $type->setId($programType);
                $collection[] = $type;
            }

            $data['programTypes'] = $collection;
        }

        if (isset($data['end_date'])) {
            $data['end_date'] = $this->calculateEndDate($data);
        }

        unset($data['organization'], $data['auto_redemption'], $data['contact'], $data['accounting_contact']);
        $this->program->exchange($data);
        return true;
    }

    private function saveEntities(): bool
    {
        if ($this->program->hasContact()) {
            // Save the Contact
            if ($this->contactRepository->place($this->program->getContact()) === false) {
                $this->repository->setErrors([_('Contact Failed to save')]);
                return false;
            }
        }

        if ($this->program->hasAccountingContact()) {
            // Save the Contact
            if ($this->contactRepository->place($this->program->getAccountingContact()) === false) {
                $this->repository->setErrors([_('Accounting Contact Failed to save')]);
                return false;
            }
        }

        if ($this->repository->insert($this->program->toArray()) === false) {
            return false; //errors already set
        }

        $programId = $this->repository->getLastInsertId();
        if (empty($programId) === true) {
            $this->repository->setErrors([_('ProgramId not found from insert')]);
            return false;
        }

        if ($this->program->getProgramTypes() !== null) {
            try {
                $this->repository->placeProgramTypes($programId, $this->program->getProgramTypes());
            } catch (Exception $e) {
                $this->repository->setErrors([_($e->getMessage())]);
                return false;
            }
        }

        $this->queueEvent('Program.create', $programId);
        return true;
    }

    private function queueEvent($name, $id)
    {
        $event = new Event();
        $event->setName($name);
        $event->setEntityId($id);
        $this->eventPublisher->publish(json_encode($event));
    }

    private function updateEntities(): bool
    {
        if ($this->program->hasContact()) {
            // Save the Contact
            if ($this->contactRepository->place($this->program->getContact()) === false) {
                $this->repository->setErrors([
                    _('The contact is missing or invalid.')
                ]);
                return false;
            }
        }

        if ($this->program->hasAccountingContact()) {
            // Save the Contact
            if ($this->contactRepository->place($this->program->getAccountingContact()) === false) {
                return false;
            }
        }

        if ($this->program->getProgramTypes() !== null) {
            try {
                $this->repository->placeProgramTypes($this->program->getId(), $this->program->getProgramTypes());
            } catch (Exception $e) {
                $this->repository->setErrors([_($e->getMessage())]);
                return false;
            }
        }

        if ($this->repository->update($this->program->getId(), $this->program->toArray()) === false) {
            return false;
        }

        $this->queueEvent('Program.update', $this->program->getId());

        return true;
    }

    /**
     * @param InputNormalizer $input
     * @return bool|\Entities\Program|null
     */
    public function insert(InputNormalizer $input)
    {
        $data = $input->getInput();
        $this->program = new \Entities\Program;
        if ($this->buildEntities($data) === false) {
            return false;
        }

        if (!empty($data['url']) && $this->isUrlValid($data['url']) === false) {
            $this->repository->setErrors([
                _('The marketplace URL provided is invalid. Please ensure the URL is a proper subdomain')
            ]);
            return false;
        }

        if (!$this->repository->isProgramIdUnique($this->program->getUniqueId())) {
            // unique_id has already been assigned to another Organization.
            return false;
        }

        if (!$this->entitiesAreValid()) {
            // At least one entity failed to validate.
            return false;
        }

        $saved = $this->saveEntities();
        if ($saved === true) {
            return $this->repository->getProgram($this->program->getUniqueId());
        }

        return false;
    }

    /**
     * @param $id
     * @param InputNormalizer $input
     * @return bool|\Entities\Program|null
     */
    public function update($id, InputNormalizer $input)
    {
        $data = $input->getInput();
        $this->program = $this->getSingle($id);
        unset($data['unique_id']); // We don't allow unique id lookup

        if ($this->program === null) {
            $this->repository->setErrors([
                _('Program with ID: ' . $id . ' does not exist.')
            ]);
            return false;
        }

        if (!empty($data['url']) && $this->isUrlValid($data['url']) === false) {
            $this->repository->setErrors([
                _('The marketplace URL provided is invalid. Please ensure the URL is a proper subdomain.')
            ]);
            return false;
        }

        if (!$this->isValidJson($data)) {
            $this->repository->setErrors([
                _('Invalid JSON.')
            ]);
            return false;
        }

        if (!$this->isValidDateFormat($data['start_date'])) {
            $this->repository->setErrors([
                _('Invalid Start Date.')
            ]);
            return false;
        }

        if (!$this->isValidDateFormat($data['end_date'])) {
            $this->repository->setErrors([
                _('Invalid End Date.')
            ]);
            return false;
        }

        if (!array_key_exists($data['timezone'], $this->timeZones['America'])) {
            $this->repository->setErrors([
                _('Invalid Timezone.')
            ]);
            return false;
        }

        if ($data['active'] == 0) {
            $data['published'] = 0;
        }

        if ($this->buildEntities($data) === false) {
            return false;
        }

        if (!$this->entitiesAreValid()) {
            // At least one entity failed to validate.
            return false;
        }

        if ($this->updateEntities() === true) {
            return $this->repository->getProgram($this->program->getUniqueId());
        }

    }

    private function isUrlValid($url)
    {
        $url = explode('.', $url);
        if (count($url) < 3 || trim($url[0]) === "") {
            return false;
        }

        array_shift($url);
        $domainString = implode('.', $url);
        $domain = $this->repository->getProgramDomainByDomainName($domainString);
        if ($domain === null) {
            return false;
        }

        return true;
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }

    /**
     * Determines if Entities are valid.
     *
     * @return bool
     */
    protected function entitiesAreValid(): bool
    {
        if ($this->program->hasContact()
            && !$this->contactRepository->validate($this->program->getContact())) {
            $this->repository->setErrors($this->contactRepository->getErrors());
            return false;
        }

        if ($this->program->hasAccountingContact()
            && !$this->contactRepository->validate($this->program->getAccountingContact())) {
            $this->repository->setErrors($this->contactRepository->getErrors());
            return false;
        }

        if (!$this->repository->validate($this->program)) {
            return false;
        }

        return true;
    }

    /**
     * @param $data
     * @return bool|string|null
     * @throws Exception
     */
    private function calculateEndDate($data)
    {
        if (empty($data['end_date']) === true) {
            return null;
        }

        $timezone = $this->program->getTimezone() ?? 'America/Phoenix';
        if (!empty($data['timezone'])) {
            $timezone = $data['timezone'];
        }

        $time = new \DateTime($data['end_date'], new \DateTimeZone($timezone));
        $time->setTimezone(new \DateTimeZone("UTC"));
        return $time->format('Y-m-d H:i:s');
    }
}

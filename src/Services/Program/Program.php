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
                    _('The provided contact was invalid.')
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

        
        if (!empty($data['start_date']) && $this->isValidDateFormat($data['start_date']) === false) {
            $this->repository->setErrors([
                _('Invalid Start Date.')
            ]);
            return false;
        }

        if (!empty($data['end_date']) && $this->isValidDateFormat($data['end_date']) === false) {
            $this->repository->setErrors([
                _('Invalid End Date.')
            ]);
            return false;
        }

        if (!empty($data['timezone']) && $this->program->isValidTimezoneId($data['timezone']) === false) {
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

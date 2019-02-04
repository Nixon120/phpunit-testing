<?php

namespace Services\Program;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Interfaces as Interfaces;
use Controllers\Program\InputNormalizer;
use Entities\AutoRedemption;
use Entities\Contact;
use Entities\Event;
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

    public function getUsers($id, $input)
    {
        return $this->repository->getUsers($id, $input);
    }

    public function getAdjustments($input)
    {
        return $this->repository->getCreditAdjustmentsByParticipant($input);
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new FilterNormalizer($input->getInput());
        $programs = $this->repository->getCollection($filter, $input->getOffset(), 30);
        return $programs;
    }

    private function buildEntities($data)
    {
        if (!empty($data['organization'])) {
            $organization = $this->repository->getProgramOrganization($data['organization'], true);
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

        if (!empty($data['url']) && $this->isUrlValid($data['url']) === true) {
            $url = explode('.', $data['url']);
            $subdomain = $url[0];
            array_shift($url);
            $domainString = implode('.', $url);
            $domain = $this->repository->getProgramDomainByDomainName($domainString);
            $data['url'] = $subdomain;
            $data['domain_id'] = $domain->getId();
        }

        unset($data['organization'], $data['auto_redemption'], $data['contact'], $data['accounting_contact']);
        $this->program->exchange($data);
    }

    private function saveEntities()
    {
        if ($this->program->hasContact()) {
            // Save the Contact
            $this->contactRepository->place($this->program->getContact());
        }

        if ($this->program->hasAccountingContact()) {
            // Save the Contact
            $this->contactRepository->place($this->program->getAccountingContact());
        }

        $this->repository->insert($this->program->toArray());
        $programId = $this->repository->getLastInsertId();

        if ($this->program->getAutoRedemption()) {
            $autoRedemption = $this->program->getAutoRedemption();
            $autoRedemption->setProgramId($programId);
            $this->repository->placeSettings($autoRedemption);
        }

        $this->queueEvent('Program.create', $programId);
    }

    private function queueEvent($name, $id)
    {
        $event = new Event();
        $event->setName($name);
        $event->setEntityId($id);
        $this->eventPublisher->publish(json_encode($event));
    }

    private function updateEntities()
    {
        if ($this->program->getContact() instanceof Contact) {
            // Save the Contact
            $this->contactRepository->place($this->program->getContact());
        }

        if ($this->program->hasAccountingContact()) {
            // Save the Contact
            $this->contactRepository->place($this->program->getAccountingContact());
        }

        if ($this->repository->update($this->program->getId(), $this->program->toArray())) {
            if ($this->program->getAutoRedemption()) {
                $autoRedemption = $this->program->getAutoRedemption();
                $autoRedemption->setProgramId($this->program->getId());
                $this->repository->placeSettings($autoRedemption);
            }
        }

        $this->queueEvent('Program.update', $this->program->getId());
    }

    /**
     * @param InputNormalizer $input
     * @return bool|\Entities\Program|null
     */
    public function insert(InputNormalizer $input)
    {
        $data = $input->getInput();
        $this->program = new \Entities\Program;
        $this->buildEntities($data);

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

        $this->saveEntities();
        return $this->repository->getProgram($this->program->getUniqueId());
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
                _('The marketplace URL provided is invalid. Please ensure the URL is a proper subdomain')
            ]);
            return false;
        }

        $this->buildEntities($data);

        if (!$this->entitiesAreValid()) {
            // At least one entity failed to validate.
            return false;
        }


        $this->updateEntities();
        return $this->repository->getProgram($this->program->getUniqueId());
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
}

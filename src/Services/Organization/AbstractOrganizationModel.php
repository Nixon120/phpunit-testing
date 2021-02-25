<?php

namespace Services\Organization;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\IndustryProgramEnum\IndustryProgramEnum;
use Entities\Contact;
use Entities\Event;
use Repositories\ContactRepository;
use Repositories\DomainRepository;
use Repositories\OrganizationRepository;
use Services\Organization\NestedSet\NestedSet;

abstract class AbstractOrganizationModel
{
    /**
     * @var OrganizationRepository
     */
    protected $repository;

    /**
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var NestedSet
     */
    protected $tree;

    /**
     * @var \Entities\Organization
     */
    protected $organization;

    /**
     * @var \Entities\Domain[]
     */
    protected $domains = [];

    /**
     * @var Contact
     */
    protected $companyContact;

    /**
     * @var Contact
     */
    protected $accountsPayableContact;

    /**
     * @var MessagePublisher
     */
    protected $eventPublisher;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $existingDomains = [];

    public function __construct(
        OrganizationRepository $repository,
        DomainRepository $domainRepository,
        ContactRepository $contactRepository,
        NestedSet $tree,
        MessagePublisher $eventPublisher
    ) {
        $this->repository = $repository;
        $this->domainRepository = $domainRepository;
        $this->contactRepository = $contactRepository;
        $this->tree = $tree;
        $this->eventPublisher = $eventPublisher;
    }

    public function getErrors()
    {
        return array_merge(
            $this->errors,
            $this->repository->getErrors(),
            $this->domainRepository->getErrors(),
            $this->contactRepository->getErrors()
        );
    }

    protected function buildEntities($data)
    {
        if (!empty($data['company_contact'])) {
            // Build the Company Contact entity.
            $this->buildCompanyContactEntity(
                $data['company_contact']
            );
            unset($data['company_contact']);
        }

        if (!empty($data['accounts_payable_contact'])) {
            // Build the Accounts Payable entity.
            $this->buildAccountsPayableContactEntity(
                $data['accounts_payable_contact']
            );
            unset($data['accounts_payable_contact']);
        }

        $this->buildOrganizationEntity($data);

        if (!empty($data['domains'])) {
            $this->domains = $this->buildDomainEntities($data['domains']);
        }
    }

    protected function buildCompanyContactEntity($contactData)
    {
        $this->companyContact = $this->organization->getCompanyContact();
        $this->companyContact->hydrate($contactData);
    }

    protected function buildAccountsPayableContactEntity($contactData)
    {
        $this->accountsPayableContact = $this->organization->getAccountsPayableContact();
        $this->accountsPayableContact->hydrate($contactData);
    }

    protected function buildOrganizationEntity($data)
    {
        if (!empty($data['parent'])) {
            $parent = $this
                ->repository
                ->getOrganization(
                    $data['parent'],
                    true
                );
            $data['parent_id'] = $parent->getId();
        }

        unset($data['parent']);

        $industryProgram = $data['industry_program'] ?? null;
        if (empty($industryProgram) === false) {
            $data['industry_program'] = (new IndustryProgramEnum())->hydrate($industryProgram);
        }

        $this->organization->exchange($data);
    }

    protected function setExistingDomains()
    {
        $orgDomains = $this->repository->getOrganizationDomains($this->organization->getUniqueId());
        foreach ($orgDomains as $domain) {
            $this->existingDomains[] = $domain->url;
        }
    }

    protected function urlExists($url)
    {
        return in_array($url, $this->existingDomains);
    }

    protected function buildDomainEntities($input):?array
    {
        $domains = [];
        $this->setExistingDomains();

        $oDomain = new \Entities\Domain;
        foreach ($input as $url) {
            if (!$this->urlExists($url)) {
                $domain = clone $oDomain;
                $domain->setUrl(strtolower($url));
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    protected function isOrganizationParentNestedLocally()
    {
        if ($this->organization->getParentId() !== null &&
            $this->organization->getLft() !== null &&
            $this->organization->getRgt() !== null
        ) {
            $parent = $this->repository->getOrganization($this->organization->getParentId());
            if ($this->organization->getLft() <= $parent->getLft() || $this->organization->getRgt() >= $parent->getRgt()) {
                return true;
            }
        }

        return false;
    }

    protected function orgIdIsUnique($unique_id)
    {
        $exists = $this
            ->repository
            ->getOrganization(
                $unique_id,
                true
            );

        if (is_null($exists)) {
            return true;
        }

        return false;
    }

    protected function queueEvent($name, $id)
    {
        $event = new Event;
        $event->setName($name);
        $event->setEntityId($id);
        $this->eventPublisher->publish(json_encode($event));
    }
}

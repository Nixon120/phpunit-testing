<?php

namespace Services\Organization;

use Entities\Contact;
use Entities\Event;
use Particle\Validator\Rule\NotEmpty;

class CreateOrganizationModel extends AbstractOrganizationModel
{
    /**
     * @param $data
     * @return bool|\Entities\Organization
     */
    public function insert($data)
    {
        if ($this->hasValidOrgNameAndUniqueId($data) === false) {
            return false;
        }

        $this->organization = new \Entities\Organization;

        $this->buildEntities($data);

        if (!$this->orgIdIsUnique($this->organization->getUniqueId())) {
            // unique_id has already been assigned to another Organization.
            $this->errors = [
                'uniqueId' => [
                    'Unique::NOT_UNIQUE' => _("The organization id has already been assigned to another organization.")
                ]
            ];

            return false;
        }

        if ($this->isOrganizationParentNestedLocally()) {
            $this->errors = [
                'parent' => [
                    'Nested::INVALID_NEST_ASSIGNMENT' => _("'You can not nest an Organization within itself or an organization it has ownership of.'")
                ]
            ];
            return false;
        }

        $this->saveEntities();

        return $this->repository->getOrganization(
            $this->organization->getUniqueId(),
            true,
            true
        );
    }

    /**
     * This should never happen due to validation
     * however, it did so we add this check
     * @param array $data
     * @return bool
     */
    private function hasValidOrgNameAndUniqueId(array $data): bool
    {
        if (empty($data['name']) === true) {
            $this->errors = [
                'name' => [
                    'NotEmpty::EMPTY_VALUE' => _("The organization name must not be empty")
                ]
            ];

            return false;
        }
        if (empty($data['unique_id']) === true) {
            $this->errors = [
                'unique_id' => [
                    'NotEmpty::EMPTY_VALUE' => _("The organization unique id must not be empty")
                ]
            ];

            return false;
        }
        return true;
    }

    private function saveEntities()
    {
        if ($this->companyContact instanceof Contact) {
            // Save the Company Contact
            $this->contactRepository->place($this->companyContact);
            $this->organization->setCompanyContactReference($this->companyContact->getReferenceId());
        }

        if ($this->accountsPayableContact instanceof Contact) {
            // Save the Accounts Payable Contact
            $this->contactRepository->place($this->accountsPayableContact);
            $this->organization->setAccountsPayableContactReference($this->accountsPayableContact->getReferenceId());
        }

        // Save the Organization.
        $this->repository->insert($this->organization->toArray());
        $organizationId = $this->repository->getLastInsertId();

        if ($this->organization->getParentId() === null) {
            $this->tree->createRootNode($organizationId);
        } else {
            $this->tree->insertChildNode(
                $organizationId,
                $this->organization->getParentId()
            );
        }

        if (!empty($this->domains)) {
            // Save the domains.
            $this->domainRepository->set($organizationId, $this->domains);
        }

        $this->queueEvent('Organization.create', $organizationId);
    }
}

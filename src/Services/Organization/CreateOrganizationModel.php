<?php

namespace Services\Organization;

use Entities\Contact;
use Entities\Event;

class CreateOrganizationModel extends AbstractOrganizationModel
{
    /**
     * @param $data
     * @return bool|\Entities\Organization
     */
    public function insert($data)
    {
        $this->organization = new \Entities\Organization;

        if (!empty($data['parent']) && !$this->repository->getOrganization($data['parent'], true)) {
            $this->errors = [
                'parent' => [
                    'parent::DOES_NOT_EXIST' => _("Parent organization does not exist or is not assignable.")
                ]
            ];

            return false;
        }

        $this->buildEntities($data);

        if (!$this->orgIdIsUnique($this->organization->getUniqueId())) {
            // unique_id has already been assigned to another Organization.
            $this->errors = [
                'unique_id' => [
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

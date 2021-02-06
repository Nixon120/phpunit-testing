<?php

namespace Services\Organization;

use Controllers\Interfaces as Interfaces;
use Entities\Contact;
use Entities\Event;

class UpdateOrganizationModel extends AbstractOrganizationModel
{
    public function getSingle($id, $uniqueIdLookup = true): ?\Entities\Organization
    {
        if ($organization = $this->repository->getOrganization($id, $uniqueIdLookup)) {
            if ($organization->getCompanyContactReference()) {
                $organization->setCompanyContact(
                    $this->contactRepository->getContact(
                        $organization->getCompanyContactReference()
                    )
                );
            }

            if ($organization->getAccountsPayableContactReference()) {
                $organization->setAccountsPayableContact(
                    $this->contactRepository->getContact(
                        $organization->getAccountsPayableContactReference()
                    )
                );
            }

            return $organization;
        }

        return null;
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new FilterNormalizer($input->getInput());
        $organizations = $this->repository->getCollection($filter, $input->getPage(), $input->getLimit());
        return $organizations;
    }

    public function deleteDomain($id)
    {
        return $this->domainRepository->delete($id);
    }

    /**
     * @param $id
     * @param $data
     * @return bool|\Entities\Organization
     */
    public function update($id, $data)
    {
        // Fetch the existing Org from DB
        $this->organization = $this->getSingle($id);

        unset($data['parent'], $data['unique_id']);

        // Hydrate Entities with new data.
        $this->buildEntities($data);
        if ($this->isOrganizationParentNestedLocally()) {
            $this->errors = [
                'parent' => [
                    'Nested::INVALID_NEST_ASSIGNMENT' => _("You can not nest an Organization within itself or an organization it has ownership of.")
                ]
            ];

            return false;
        }

        $this->saveEntities();

        return $this->repository->getOrganization($id, true);
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

        // Save Organization
        $this->repository->update(
            $this->organization->getId(),
            $this->organization->toArray()
        );

        if ($this->organization->getParentId() !== null) {
            $this->tree->insertChildNode(
                $this->organization->getUniqueId(),
                $this->organization->getParentId()
            );
        }

        if (!empty($this->domains)) {
            // Save Domains
            $this->domainRepository->set($this->organization->getId(), $this->domains);
        }

        $this->queueEvent('Organization.update', $this->organization->getId());
    }
}

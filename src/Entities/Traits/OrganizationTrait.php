<?php
namespace Entities\Traits;

use Entities\Organization;

trait OrganizationTrait
{
    public $organization_id;

    private $organization;

    /**
     * @return mixed
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * @param mixed $id
     */
    public function setOrganizationId($id)
    {
        $this->organization_id= $id;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @param Organization|null $organization
     */
    public function setOrganization(?Organization $organization)
    {
        $this->organization = $organization;
    }
}

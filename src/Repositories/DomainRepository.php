<?php

namespace Repositories;

use Entities\Domain;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class DomainRepository extends BaseRepository
{
    protected $table = 'Domain';

    /**
     * @var Domain
     */
    protected $entity;

    public function getRepositoryEntity()
    {
        return Domain::class;
    }

    public function getDomain($id):?Domain
    {
        $sql = "SELECT * FROM Domain WHERE id = ?";
        return $this->query($sql, [$id], Domain::class);
    }

    public function set($organizationId, array $domains)
    {
        foreach ($domains as $domain) {
            /** @var $domain Domain */
            $domain->setOrganizationId($organizationId);
            if (!$this->place($domain)) {
                return false;
            }
        }

        return true;
    }
}

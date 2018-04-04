<?php

namespace Repositories;

use Entities\Domain;
use \PDO as PDO;
use Entities\Organization;

class OrganizationRepository extends BaseRepository
{
    protected $table = 'Organization';

    public $orderBy = ' ORDER BY lft';

    public $groupBy = ' GROUP BY a.id';

    public function getRepositoryEntity()
    {
        return Organization::class;
    }

    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());

            $where = <<<SQL
WHERE a.id IN ({$organizationIdString})
SQL;
        }

        $sql = <<<SQL
SELECT a.name, a.active, a.unique_id,
a.lvl, (SELECT c.unique_id FROM Organization c WHERE c.id = a.parent_id) AS parent_id,
(SELECT COUNT(Program.id) FROM Program WHERE Program.organization_id = a.id) AS program_count
FROM Organization a
LEFT JOIN Domain ON a.id = Domain.organization_id 
{$where}
SQL;


        return $sql;
    }

    //@TODO change this to getByUnique or getById
    public function getOrganization($id, $unique = false, $skipOwnershipCheck = false):?Organization
    {
        $identifier = $unique ? 'unique_id' : 'id';
        $sql = <<<SQL
SELECT * FROM Organization WHERE {$identifier} = ?
SQL;
        if (!empty($this->getOrganizationIdContainer()) && $skipOwnershipCheck === false) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $sql .= <<<SQL
 AND Organization.id IN ({$organizationIdString});
SQL;
        }
        $args = [$id];
        /** @var Organization $organization */
        if (!$organization = $this->query($sql, $args, Organization::class)) {
            return null;
        }

        if ($organization->getParentId() !== null) {
            $organization->setParent($this->getOrganization($organization->getParentId()));
        }

        $organization->setDomains($this->getOrganizationDomains($organization->getUniqueId()));
        return $organization;
    }

    public function getOrganizationDomains($uniqueId):?array
    {
        $sql = "SELECT * FROM `Domain`";
        $innerQuery = "SELECT parent.id FROM Organization node, Organization parent"
            . " WHERE (node.lft BETWEEN parent.lft AND parent.rgt)"
            . " AND node.unique_id = ?"
            . " ORDER BY parent.rgt - parent.lft DESC";

        $sql .= " WHERE organization_id IN (" . $innerQuery . ")";
        $args = [$uniqueId];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $domains = $sth->fetchAll(PDO::FETCH_CLASS, Domain::class);

        return $domains;
    }
}

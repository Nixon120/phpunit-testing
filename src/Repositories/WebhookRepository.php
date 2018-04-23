<?php

namespace Repositories;

use Entities\Organization;
use Entities\Webhook;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;
use Services\Webhook\FilterNormalizer;

class WebhookRepository extends BaseRepository
{
    protected $table = 'webhook';

    /**
     * @var Webhook
     */
    protected $entity;

    public function getRepositoryEntity()
    {
        return Webhook::class;
    }

    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $where = <<<SQL
 WHERE Program.organization_id IN ({$organizationIdString})
SQL;
        }
        $string = <<<SQL
SELECT * 
FROM Webhook
JOIN Organization ON Organization.id = Webhook.organization_id
{$where}
SQL;
        return $string;
    }

    public function getOrganizationWebhooks(Organization $organization)
    {
        $filters = new FilterNormalizer(
            ['organization_id' => $organization->getId()]
        );

        return $this->getCollection($filters, 0, 100);
    }

    /**
     * @param int $id
     * @return Webhook
     */
    public function getWebhook(int $id)
    {
        $sql = "SELECT * FROM webhook WHERE id = ?";

        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $sql .= <<<SQL
 AND webhook.organization_id IN ({$organizationIdString});
SQL;
        }

        return $this->query($sql, [$id], Webhook::class);
    }

    /**
     * @param Organization $organization Organization
     * @param string $eventName Event Name
     * @return Webhook[]
     */
    public function getOrganizationAndParentWebhooks(
        Organization $organization,
        string $eventName = ''
    ) {
        // If $uniqueId is a top level parent, this will fail.
        $sql = "SELECT * FROM `webhook`";
        $innerQuery = "SELECT parent.id FROM Organization node, Organization parent"
            . " WHERE (node.lft BETWEEN parent.lft AND parent.rgt)"
            . " AND node.unique_id = ?"
            . " ORDER BY parent.rgt - parent.lft DESC";

        $sql .= " WHERE organization_id IN (" . $innerQuery . ")";

        $args = [$organization->getUniqueId()];

        if (!empty($eventName)) {
            $sql .= " AND event = ?";
            array_push($args, $eventName);
        }

        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $webhooks = $sth->fetchAll(
            \PDO::FETCH_CLASS,
            $this->getRepositoryEntity()
        );

        return $webhooks;
    }


    public function updateWebhook(int $id, array $data)
    {
        $active = empty($data['active']) ? 0 : 1;

        $sql = <<<SQL
UPDATE `webhook` 
SET title = ?, 
    url = ?, 
    active = $active, 
    event = ?, 
    username = ?, 
    password = ?
    WHERE id = $id
SQL;
        $sth = $this->database->prepare($sql);
        $params = [
            $data['title'],
            $data['url'],
            $data['event'],
            $data['username'],
            $data['password']
        ];
        return $sth->execute($params);
    }

    /**
     * @param Webhook $webhook
     * @return bool
     */
    public function isValid(Webhook $webhook)
    {
        try {
            $this->getValidator()->assert((object)$webhook->toArray());
        } catch (NestedValidationException$exception) {
            $this->errors = $exception->getMessages();
            return false;
        }

        return true;
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return Validator::attribute(
            'title',
            Validator::stringType()
                ->length(1, 255)
                ->setName('Title')
        )->attribute(
            'url',
            Validator::url()
                ->length(11, 255)
                ->setName('URL')
        )->attribute(
            'username',
            Validator::optional(
                Validator::stringType()
                    ->length(0, 255)
            )->setName('Username')
        )->attribute(
            'password',
            Validator::optional(
                Validator::stringType()
                    ->length(0, 255)
            )->setName('Password')
        );
    }
}

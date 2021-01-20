<?php
namespace Repositories;

use Entities\Base;
use \PDO as PDO;
use Repositories\Interfaces\Repository;
use Services\Interfaces\FilterNormalizer;
use Traits\DatabaseTrait;

abstract class BaseRepository implements Repository
{
    use DatabaseTrait;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var PDO
     */
    protected $database;

    /**
     * @var array list of strings to match against for skipping
     */
    protected $skip = [];

    //@TODO add getter/setters
    protected $table;

    public $orderBy;

    public $groupBy;

    /**
     * @var array
     */
    private $programIdContainer = [];

    private $organizationIdContainer = [];

    public function __construct(PDO $database)
    {
        $this->database = $database;
    }

    /**
     * @return mixed
     */
    public function getOrderBySql()
    {
        if($this->getOrderBy() === null) {
            return "";
        }
        foreach($this->getOrderBy() as $orderBy) {
            $orderByCollection[] = "`{$orderBy['column']}` {$orderBy['direction']}";
        }
        $orderBySql = implode(',', $orderByCollection);
        return <<<SQL
 ORDER BY {$orderBySql}
SQL;
    }

    /**
     * @param mixed $orderBy
     */
    public function setOrderBy(array $orderBy): void
    {
        $collection = [];
        foreach($orderBy as $key => $order) {
            $collection[] = [
                'column' => $this->getSafeColumnName($key),
                'direction' => strtolower($order) !== 'asc' ? 'DESC' : 'ASC'
            ];
        }

        $this->orderBy = $collection;
    }

    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    /**
     * @return array
     */
    public function getProgramIdContainer(): array
    {
        return $this->programIdContainer;
    }

    /**
     * @param array $programIdContainer
     */
    public function setProgramIdContainer(array $programIdContainer)
    {
        $this->programIdContainer = $programIdContainer;
    }

    /**
     * @return mixed
     */
    public function getOrganizationIdContainer()
    {
        return $this->organizationIdContainer;
    }

    /**
     * @param mixed $organizationIdContainer
     */
    public function setOrganizationIdContainer($organizationIdContainer)
    {
        $this->organizationIdContainer = $organizationIdContainer;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    public function setSkip(array $skipFields)
    {
        $this->skip = $skipFields;
    }

    public function getLastInsertId()
    {
        return $this->database->lastInsertId();
    }

    public function place(Base $entity)
    {
        return $this->insert($entity->toArray(), true);
    }

    public function insert(array $data, $unique = false)
    {
        var_dump('adjustmentInsert: ' . $data['amount']);

        $sql = $this->generateInsertSQL($data);
        $params = $this->generateInsertParameters($data);

        if ($unique) {
            $fields = $this->getFields($data);
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->generateDuplicateKeySql($fields);
        }

        //@TODO last insert id on transactions..
        $sth = $this->database->prepare($sql);
        $success = false;

        try {
            $sth->execute($params);
            $success = true;
        } catch (\PDOException $e) {
            $this->errors[] = $e->getMessage();
        }

        return $success;
    }

    public function update($id, $data)
    {
        if (empty($data)) {
            //nothing to save here? let's just return true..
            //@TODO think about it.
            return true;
        }

        $sql = $this->generateUpdateSQL($data);
        $params = $this->generateUpdateParameters($id, $data);

        $sth = $this->database->prepare($sql);
        $this->database->beginTransaction();

        try {
            $sth->execute($params);
        } catch (\PDOException $e) {
            $this->errors[] = $e->getMessage();
        }
        $commit = $this->database->commit();
        return $commit;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM `" . $this->table . "` WHERE id = ?";
        $sth = $this->database->prepare($sql);
        return $sth->execute([$id]);
    }

    public function batch(array $batch, $unique = false): bool
    {
        $sql = $this->generateBatchSQL($batch);
        $params = $this->generateBatchParameters($batch);

        if ($unique) {
            $fields = $this->getFields($batch[0]->toArray());
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->generateDuplicateKeySql($fields);
        }

        $sth = $this->database->prepare($sql);

        $this->database->beginTransaction();
        try {
            $sth->execute($params);
            return $this->database->commit();
        } catch (\PDOException $e) {
            //@TODO Handle exception
            echo $e->getMessage();
        }

        return false;
    }

    public function query($sql, array $args = [], $class)
    {
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $sth->setFetchMode(PDO::FETCH_CLASS, $class);
        $row = $sth->fetch();

        if (!$row) {
            return null;
        }

        return $row;
    }

    public function getCollection(
        FilterNormalizer $filters = null,
        $page = 1,
        $limit = 30
    ) {
        $page = $page < 1 ? 1 : $page;
        $offset = ((int)($page-1)) * ((int)$limit);
        $sql = $this->getCollectionQuery() . ' ';
        $args = [];

        if ($filters !== null && trim($filters->getFilterConditionSql()) !== '') {
            $sql .= $filters->getFilterConditionSql();
        }

        if ($this->groupBy !== null) {
            $sql .= $this->groupBy;
        }

        if($filters->getOrderBy() !== null) {
            $this->setOrderBy($filters->getOrderBy());
            $sql .= $this->getOrderBySql();
        }

        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;
        if ($filters !== null) {
            $args = $filters->getFilterConditionArgs();
        }

        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        return $sth->fetchAll(
            PDO::FETCH_CLASS,
            $this->getRepositoryEntity()
        );
    }

    public function getCollectionQuery(): string
    {
        $string = "SELECT * FROM " . $this->table;
        return $string;
    }

    private function getSafeColumnName(string $column): ?string
    {
        $sql = <<<SQL
SELECT column_name, ordinal_position
FROM information_schema.columns
WHERE table_name = '{$this->table}'
SQL;

        $sth = $this->getDatabase()->query($sql);
        $columnResult = $sth->fetchAll();
        foreach($columnResult as $result) {
            if(strtolower($result['column_name']) === strtolower($column)) {
                return (string) $result['column_name'];
            }
        }

        // throw exception
        throw new \Exception('Unable to find field: ' . $column . ' to sort by');
    }
}

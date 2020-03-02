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

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy) 
    {
        $this->orderBy = $orderBy;
    }

    public function getCollection(
        FilterNormalizer $filters = null,
        $offset = 30,
        // Why default offset to 30?
        $limit = 30
    ) {
        $sql = $this->getCollectionQuery() . ' ';
        $args = [];

        if ($filters !== null) {
            $sql .= $filters->getFilterConditionSql();
        }

        if ($this->groupBy !== null) {
            $sql .= $this->groupBy;
        }

        if ($this->orderBy !== null) {
            $sql .= $this->orderBy;
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
}

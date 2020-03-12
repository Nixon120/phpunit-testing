<?php

namespace Repositories;

use Entities\Program;
use Entities\ProgramType;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class ProgramTypeRepository extends BaseRepository
{
    protected $table = 'ProgramType';

    /**
     * @return string
     */
    public function getRepositoryEntity()
    {
        return ProgramType::class;
    }

    /**
     * @return string
     */
    public function getCollectionQuery(): string
    {
        $where = " WHERE 1 = 1 ";

        $sql = <<<SQL
SELECT ProgramType.* 
FROM ProgramType
{$where}
ORDER BY `ProgramType`.name ASC
SQL;

        return $sql;
    }

    /**
     * @param $id
     * @return ProgramType|null
     */
    public function getProgramType($id): ?ProgramType
    {
        $sql = "SELECT * FROM ProgramType WHERE id = ?";

        $args = [$id];

        if (!$programType = $this->query($sql, $args, ProgramType::class)) {
            return null;
        }

        return $programType;
    }

    public function isProgramTypeInUse(int $typeId): bool
    {
        $sql = <<<SQL
SELECT id 
FROM ProgramToProgramType
WHERE program_type_id = ?
LIMIT 1
SQL;

        $sth = $this->getDatabase()->prepare($sql);
        $sth->execute([$typeId]);
        $anId = $sth->fetchColumn(0);

        if (!empty($anId)) {
            return true;
        }

        return false;
    }

    public function deleteProgramType($id): bool
    {
        $sql = <<<SQL
DELETE FROM ProgramType
WHERE id = ?
SQL;

        $sth = $this->getDatabase()->prepare($sql);

        return $sth->execute([$id]);
    }

    /**
     * @param ProgramType $programType
     * @return bool
     */
    public function validate(\Entities\ProgramType $programType): bool
    {
        try {
            $stdProgramType = (object)$programType->toArray();
            $this->getValidator()->assert($stdProgramType);
            return true;
        } catch (NestedValidationException $exception) {
            $this->errors = $exception->getMessages();
            return false;
        }
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        $validator = Validator::attribute('name', Validator::notEmpty()->setName('Name'))
            ->attribute('description', Validator::optional(Validator::stringType()->setName('Description')));

        return $validator;
    }
}

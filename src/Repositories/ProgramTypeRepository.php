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
SQL;

        return $sql;
    }

    /**
     * @param $id
     * @return Program|null
     */
    public function getProgramType($id): ?Program
    {
        $sql = "SELECT * FROM ProgramType WHERE id = ?";

        $args = [$id];

        if (!$programType = $this->query($sql, $args, ProgramType::class)) {
            return null;
        }

        return $programType;
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

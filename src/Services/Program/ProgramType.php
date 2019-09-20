<?php

namespace Services\Program;

use Controllers\Interfaces as Interfaces;
use Controllers\Program\InputNormalizer;
use Controllers\Program\ProgramTypeInputNormalizer;
use Repositories\ProgramTypeRepository;

class ProgramType
{
    /**
     * @var ProgramTypeRepository
     */
    public $repository;

    /**
     * ProgramType constructor.
     * @param ProgramTypeRepository $repository
     */
    public function __construct(
        ProgramTypeRepository $repository
    ) {
    
        $this->repository = $repository;
    }

    /**
     * @param Interfaces\InputNormalizer $input
     * @return array
     */
    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new ProgramTypeFilterNormalizer($input->getInput());
        $programs = $this->repository->getCollection($filter, $input->getOffset(), 30);
        return $programs;
    }

    /**
     * @param ProgramTypeInputNormalizer $input
     * @return bool
     * @throws \Exception
     */
    public function insert(ProgramTypeInputNormalizer $input)
    {
        $data = $input->getInput();
        $actions = $data['actions'] ?? [];
        $programType = new \Entities\ProgramType($data);
        $programType->setActions($actions);

        if ($this->repository->validate($programType) === false) {
            // At least one entity failed to validate.
            return false;
        }

        return $this->repository->insert($programType->toArray());
    }

    /**
     * @param $id
     * @param ProgramTypeInputNormalizer $input
     * @return bool
     * @throws \Exception
     */
    public function update($id, ProgramTypeInputNormalizer $input)
    {
        $data = $input->getInput();
        $actions = $data['actions'] ?? [];
        $programType = new \Entities\ProgramType($data);
        $programType->setActions($actions);

        if ($this->repository->validate($programType) === false) {
            // At least one entity failed to validate.
            return false;
        }

        return $this->repository->update($id, $programType->toArray());
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}

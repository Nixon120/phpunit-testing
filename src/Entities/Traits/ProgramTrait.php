<?php
namespace Entities\Traits;

use Entities\Program;

trait ProgramTrait
{
    public $program_id;

    private $program;

    /**
     * @return mixed
     */
    public function getProgramId()
    {
        return $this->program_id;
    }

    /**
     * @param mixed $id
     */
    public function setProgramId($id)
    {
        $this->program_id= $id;
    }

    /**
     * @return Program
     */
    public function getProgram(): ?Program
    {
        return $this->program;
    }

    /**
     * @param Program|null $program
     */
    public function setProgram(?Program $program)
    {
        $this->program = $program;
    }
}

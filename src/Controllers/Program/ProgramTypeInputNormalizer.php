<?php
namespace Controllers\Program;

use Controllers\AbstractInputNormalizer;

class ProgramTypeInputNormalizer extends AbstractInputNormalizer
{
    public function getInput(): array
    {
        $input = parent::getInput();
        return $input;
    }
}

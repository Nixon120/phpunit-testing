<?php
namespace Controllers\Program;

use Controllers\AbstractOutputNormalizer;
use Entities\Program;

class ProgramTypeOutputNormalizer extends AbstractOutputNormalizer
{
    public function getList(): array
    {
        $list = parent::get();

        foreach ($list as $key => $type) {
            $list[$key]->actions = $type->getActions();
        }

        return $list;
    }
}

<?php
namespace Controllers\Program;

use Controllers\AbstractOutputNormalizer;
use Entities\Program;

class ProgramTypeOutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Program $program */
        $program = parent::get();
        return $program->toArray();
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, [
            'id'
        ]);

        foreach ($return as $key => $type) {
            if(!empty($type['actions'])) {
                $return[$key]['actions'] = json_decode($type['actions'], true);
            }
        }

        return $return;
    }
}

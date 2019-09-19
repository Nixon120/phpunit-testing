<?php
namespace Services\Program;

use Services\AbstractFilterNormalizer;

class ProgramTypeFilterNormalizer extends AbstractFilterNormalizer
{
    public function getNameFilter($value)
    {
        if ($value !== "") {
            return "`ProgramType`.`name` LIKE ?";
        }

        return false;
    }

    public function getNameFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%' . $value . '%';
        }

        return $args;
    }
}

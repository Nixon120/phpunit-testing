<?php
namespace Services\Organization;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getNameFilter($value)
    {
        if ($value !== "") {
            return "`a`.`name` LIKE ?";
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

    public function getParentFilter($value)
    {
        if ($value !== "") {
            return "(SELECT c.unique_id FROM Organization c WHERE c.id = a.parent_id) = ?";
        }

        return false;
    }

    public function getParentFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

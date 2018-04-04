<?php
namespace Services\Product;

use Services\AbstractFilterNormalizer;

class CategoryFilterNormalizer extends AbstractFilterNormalizer
{
    public function getNameFilter($value)
    {
        if ($value !== "") {
            return "`Category`.`name` LIKE ?";
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

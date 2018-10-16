<?php

namespace Services\Scheduler;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getIntervalFilter($value)
    {
        if ($value !== "") {
            return "`autoredemption`.`interval` = ?";
        }

        return false;
    }

    public function getIntervalFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getActiveFilter($value)
    {
        if ($value !== "") {
            return "`autoredemption`.`active` = ?";
        }

        return false;
    }

    public function getActiveFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

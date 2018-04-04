<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class RedemptionFilterNormalizer extends AbstractFilterNormalizer
{
    public function getOrganizationFilter($value)
    {
        if ($value !== "") {
            return "`Organization`.`unique_id` = ?";
        }

        return false;
    }

    public function getOrganizationFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getProgramFilter($value)
    {
        if ($value !== "") {
            return "`Program`.`unique_id` = ?";
        }

        return false;
    }

    public function getProgramFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getStartDateFilter($value)
    {
        if ($value !== "") {
            return "`Transaction`.`created_at` >= ?";
        }

        return false;
    }

    public function getStartDateFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getEndDateFilter($value)
    {
        if ($value !== "") {
            return "`Transaction`.`created_at` <= ?";
        }

        return false;
    }

    public function getEndDateFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getUniqueIdFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`unique_id` = ?";
        }

        return false;
    }

    public function getUniqueIdFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getFirstnameFilter($value)
    {
        if ($value !== "") {
            return "`Address`.`firstname` = ?";
        }

        return false;
    }

    public function getFirstnameFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getLastnameFilter($value)
    {
        if ($value !== "") {
            return "`Address`.`lastname` = ?";
        }

        return false;
    }

    public function getLastnameFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getAddress1Filter($value)
    {
        if ($value !== "") {
            return "`Address`.`address1` = ?";
        }

        return false;
    }

    public function getAddress1FilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getAddress2Filter($value)
    {
        if ($value !== "") {
            return "`Address`.`address2` = ?";
        }

        return false;
    }

    public function getAddress2FilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

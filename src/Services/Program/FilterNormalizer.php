<?php
namespace Services\Program;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getUniqueIdFilter($value)
    {
        if (!empty($value[0])) {
            $placeholders = rtrim(str_repeat('?, ', count($value)), ', ');
            return " `Program`.`unique_id` IN ({$placeholders})";
        }

        return false;
    }

    public function getUniqueIdFilterArgs($value)
    {
        $args = [];

        if (!empty($value)) {
            $args[] = $value;
        }

        return $args;
    }

    public function getStatusFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`Program`.`active` = ?";
        }

        return $string;
    }

    public function getStatusFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getNameFilter($value)
    {
        if ($value !== "") {
            return "`Program`.`name` LIKE ?";
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

    public function getOrganizationIdFilter($value)
    {
        if ($value !== "") {
            return "`Program`.`organization_id` = ?";
        }

        return false;
    }

    public function getOrganizationIdFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

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

    public function getUriFilter($value)
    {
        if ($value !== "") {
            return "CONCAT(`Program`.`url`,'.',`Domain`.`url`) = ?";
        }

        return false;
    }

    public function getUriFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

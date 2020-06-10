<?php
namespace Services\Participant;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getStatusFilter($value)
    {
        $string = "";

        if ($value !== "") {
            if (in_array(trim($value), ["0", "1"])) {
                return "`Participant`.`active` = ?";
            }
            //value comes in as 2 so we need to just set where frozen is 1
            $string = "`Participant`.`frozen` = 1";
        }

        return $string;
    }

    public function getStatusFilterArgs($value)
    {
        $args = [];

        if ($value !== "" && $value !== "2") {
            $args[] = trim($value);
        }

        return $args;
    }

    public function getPointsGreaterThanFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`credit` >= ?";
        }

        return false;
    }

    public function getPointsGreaterThanFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = trim($value);
        }

        return $args;
    }

    public function getUniqueIdFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`unique_id` LIKE ?";
        }

        return false;
    }

    public function getUniqueIdFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = trim($value) . '%';
        }

        return $args;
    }

    public function getEmailAddressFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`email_address` LIKE ?";
        }

        return false;
    }

    public function getEmailAddressFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%' . trim($value) . '%';
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
            $args[] = trim($value);
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
            $args[] = trim($value);
        }

        return $args;
    }

    public function getNameFilter($value)
    {
        if ($value !== "") {
            return "((`Participant`.`firstname` LIKE ? OR `Participant`.`lastname` LIKE ?) OR (concat_ws(' ', `Participant`.`firstname`, `Participant`.`lastname`) LIKE ?))";
        }

        return false;
    }

    public function getNameFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%' . trim($value) . '%';
            $args[] = '%' . trim($value) . '%';
            $args[] = '%' . trim($value) . '%';
        }

        return $args;
    }

    public function getBirthdateFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`birthdate` = ?";
        }

        return false;
    }

    public function getBirthdateFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = trim($value);
        }

        return $args;
    }
}

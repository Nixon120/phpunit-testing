<?php
namespace Services\User;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getStatusFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`User`.`active` = ?";
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

    public function getRoleFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`User`.`role` = ?";
        }

        return $string;
    }

    public function getRoleFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getEmailAddressFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`User`.`email_address` LIKE ?";
        }

        return $string;
    }

    public function getEmailAddressFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%' . $value . '%';
        }

        return $args;
    }

    public function getOrganizationFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`Organization`.`unique_id` = ?";
        }

        return $string;
    }

    public function getOrganizationFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getNameFilter($value)
    {
        $string = "";
        if ($value !== "") {
            $string = "((`User`.`firstname` LIKE ? OR `User`.`lastname` LIKE ?) OR (concat_ws(' ', `User`.`firstname`, `User`.`lastname`) LIKE ?))";
        }

        return $string;
    }

    public function getNameFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%' . $value . '%';
            $args[] = '%' . $value . '%';
            $args[] = '%' . $value . '%';
        }

        return $args;
    }
}

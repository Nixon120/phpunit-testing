<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class TransactionFilterNormalizer extends AbstractFilterNormalizer
{
    public function getStatusFilter($value)
    {
        return "`Participant`.`active` = ?";
    }

    public function getStatusFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getOrganizationFilter($value)
    {
        return "`Organization`.`unique_id` = ?";
    }

    public function getOrganizationFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getProgramFilter($value)
    {
        return "`Program`.`unique_id` = ?";
    }

    public function getProgramFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getStartDateFilter($value)
    {
        return "`Adjustment`.`created_at` >= ?";
    }

    public function getStartDateFilterArgs($value)
    {
        $value = $this->returnArg($value);
        if (empty($value) === false) {
            $value[0] = $value[0] . ' 00:00:00';
        }

        return $value;
    }

    public function getEndDateFilter($value)
    {
        return "`Adjustment`.`created_at` <= ?";
    }

    public function getEndDateFilterArgs($value)
    {
        $value = $this->returnArg($value);
        if (empty($value) === false) {
            $value[0] = $value[0] . ' 23:59:59';
        }
        return $value;
    }

    public function getUniqueIdFilter($value)
    {
        return "`Participant`.`unique_id` = ?";
    }

    public function getUniqueIdFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getFirstnameFilter($value)
    {
        return "`Address`.`firstname` = ?";
    }

    public function getFirstnameFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getLastnameFilter($value)
    {
        return "`Address`.`lastname` = ?";
    }

    public function getLastnameFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getBirthdateFilter($value)
    {
        return "`Participant`.`birthdate` = ?";
    }

    public function getBirthdateFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getAddress1Filter($value)
    {
        return "`Address`.`address1` = ?";
    }

    public function getAddress1FilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getAddress2Filter($value)
    {
        return "`Address`.`address2` = ?";
    }

    public function getAddress2FilterArgs($value)
    {
        return $this->returnArg($value);
    }
}

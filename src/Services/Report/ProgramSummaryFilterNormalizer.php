<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class ProgramSummaryFilterNormalizer extends AbstractFilterNormalizer
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
        return "`Transaction`.`created_at` >= ?";
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
        return "`Transaction`.`created_at` <= ?";
    }

    public function getEndDateFilterArgs($value)
    {
        $value = $this->returnArg($value);
        if (empty($value) === false) {
            $value[0] = $value[0] . ' 23:59:59';
        }
        return $value;
    }
}

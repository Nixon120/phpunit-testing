<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class SweepstakeFilterNormalizer extends AbstractFilterNormalizer
{
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
        return "DATE(`SweepstakeEntry`.`created_at`) >= ?";
    }

    public function getStartDateFilterArgs($value)
    {
        return $this->returnArg($value);
    }

    public function getEndDateFilter($value)
    {
        return "DATE(`SweepstakeEntry`.`created_at`) <= ?";
    }

    public function getEndDateFilterArgs($value)
    {
        return $this->returnArg($value);
    }
}

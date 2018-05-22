<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class ParticipantSummaryFilterNormalizer extends AbstractFilterNormalizer
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
}

<?php
namespace Services\Report;

use Services\AbstractFilterNormalizer;

class ParticipantSummaryFilterNormalizer extends AbstractFilterNormalizer
{
    public function getStatusFilter($value)
    {
        if ($value !== "") {
            return "`Participant`.`active` = ?";
        }

        return false;
    }

    public function getStatusFilterArgs($value)
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
}

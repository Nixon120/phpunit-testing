<?php
namespace Services\Participant;

use Services\AbstractFilterNormalizer;

class BalanceFilterNormalizer extends AbstractFilterNormalizer
{
    public function getParticipantIdFilter($value)
    {
        if ($value !== "") {
            return "`Adjustment`.`participant_id` = ?";
        }

        return false;
    }

    public function getParticipantIdFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

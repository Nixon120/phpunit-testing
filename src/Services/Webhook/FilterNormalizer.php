<?php
namespace Services\Webhook;

use Services\AbstractFilterNormalizer;

class FilterNormalizer extends AbstractFilterNormalizer
{
    public function getOrganizationIdFilter($value)
    {
        if ($value !== "") {
            return "`webhook`.`organization_id` = ?";
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
}

<?php
namespace Services\Interfaces;

interface FilterNormalizer
{
    public function getInput():?array;

    public function setInput(?array $input);

    public function getOrderBy(): ?array;

    public function getFilterConditionSql():?string;

    public function getFilterConditionArgs():?array;
}

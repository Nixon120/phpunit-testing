<?php
namespace Controllers\Interfaces;

interface InputNormalizer
{

    public function getInput():array;

    public function setInput(?array $input);

    public function getPage(): int;

    public function getLimit(): int;
}

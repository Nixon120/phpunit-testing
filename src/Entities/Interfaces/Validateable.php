<?php

namespace Entities\Interfaces;

interface Validateable
{
    public function isValid(): bool;

    public function getValidationErrors(): array;
}

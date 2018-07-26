<?php
namespace Validation\Rules;

use Particle\Validator\Rule;

class AlphanumericId extends Rule
{
    const INVALID_FORMAT = 'InvalidId::INVALID_ID';

    protected $messageTemplates = [
        self::INVALID_FORMAT => '{{ name }} should contains only alphanumeric values',
    ];

    public function validate($value)
    {
        $valueValidated = (bool) preg_match('/^[a-zA-Z0-9\-_]{0,40}$/', $value);

        if ($valueValidated === false) {
            return $this->error(self::INVALID_FORMAT);
        }
        return true;
    }
}

<?php
namespace Validation\Rules;

use Particle\Validator\Rule;

class UsPostalCode extends Rule
{
    const INVALID_FORMAT = 'UsPostalCode::INVALID_VALID';

    protected $messageTemplates = [
        self::INVALID_FORMAT => '{{ name }} must be a valid US postal code',
    ];

    public function validate($value)
    {
        $valueValidated = (bool) preg_match('#^\d{5}([\-]?\d{4})?$#', $value);

        if ($valueValidated === false) { // always true, so always grumpy!
            return $this->error(self::INVALID_FORMAT);
        }
        return true;
    }
}

<?php
namespace Validation\Rules;

use Particle\Validator\Rule;

class Domain extends Rule
{
    /**
     * A constant that will be used when the value is not a valid e-mail address.
     */
    const INVALID_DOMAIN = 'Domain::INVALID_DOMAIN';

    /**
     * The message templates which can be returned by this validator.
     *
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID_DOMAIN=> '{{ name }} must be a valid tld',
    ];

    /**
     * Validates if the value is a valid email address.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return true;
        }
        return $this->error(self::INVALID_DOMAIN);
    }
}

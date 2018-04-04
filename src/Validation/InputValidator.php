<?php
namespace Validation;

use Particle\Validator\Validator;

/**
 * @method CustomValidationChain required($key, $name = null, $allowEmpty = false)
 * @method CustomValidationChain optional($key, $name = null, $allowEmpty = true)
 */
class InputValidator extends Validator
{
    /**
     * @param string $key
     * @param string $name
     * @param bool $required
     * @param bool $allowEmpty
     * @return CustomValidationChain
     */
    protected function buildChain($key, $name, $required, $allowEmpty): CustomValidationChain
    {
        return new CustomValidationChain($key, $name, $required, $allowEmpty);
    }
}

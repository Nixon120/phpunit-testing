<?php
namespace Validation;

use Particle\Validator\Chain;
use Validation\Rules\EachIndex;
use Validation\Rules\UsPostalCode;
use Validation\Rules\Domain;

class CustomValidationChain extends Chain
{
    /**
     * @return $this
     */
    public function usPostalCode()
    {
        return $this->addRule(new UsPostalCode);
    }

    /**
     * @return $this
     */
    public function domain()
    {
        return $this->addRule(new Domain);
    }

    /**
     * Validates a value to be a nested array, which can then be validated using a new Validator instance.
     *
     * @param callable $callback
     * @return $this
     */
    public function eachIndex(callable $callback)
    {
        return $this->addRule(new EachIndex($callback));
    }
}

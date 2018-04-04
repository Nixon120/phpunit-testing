<?php
namespace Validation\Rules;

use Particle\Validator\Rule;
use Particle\Validator\ValidationResult;
use Validation\InputValidator;

class EachIndex extends Rule
{
    const NOT_AN_ARRAY = 'Each::NOT_AN_ARRAY';

    /**
     * The message templates which can be returned by this validator.
     *
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_AN_ARRAY => '{{ name }} must be an array',
    ];

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Validates if $value is array, validate each inner array of $value, and return result.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if (!is_array($value)) {
            return $this->error(self::NOT_AN_ARRAY);
        }

        $result = true;
        foreach ($value as $index => $innerValue) {
            $innerValue = [
                'index' => $innerValue
            ];
            $result = $this->validateValue($index, $innerValue) && $result;
        }
        return $result;
    }

    /**
     * This method will spawn a new validator, validate an inner array, and return its result.
     *
     * @param string $index
     * @param mixed $value
     * @return bool
     */
    protected function validateValue($index, $value)
    {
        $innerValidator = new InputValidator;

        call_user_func($this->callback, $innerValidator);

        $result = $innerValidator->validate($value);

        if (!$result->isValid()) {
            $this->handleError($index, $result);
            return false;
        }

        return true;
    }

    /**
     * @param mixed $index
     * @param ValidationResult $result
     */
    protected function handleError($index, $result)
    {
        foreach ($result->getFailures() as $failure) {
            $failure->overwriteKey(
                sprintf('%s.%s.%s', $this->key, $index, $failure->getKey())
            );

            $this->messageStack->append($failure);
        }
    }
}

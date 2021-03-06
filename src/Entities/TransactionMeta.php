<?php
namespace Entities;

use Entities\Traits\TimestampTrait;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class TransactionMeta extends Base
{
    use TimestampTrait;

    public $transaction_id;

    public $key;

    public $value;

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param mixed $id
     */
    public function setTransactionId($id)
    {
        $this->transaction_id = $id;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}

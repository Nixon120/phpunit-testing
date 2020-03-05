<?php

namespace Entities;

class TransactionItemRefund extends Base
{
    public $transaction_id;

    public $transaction_item_id;

    public $notes;

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param mixed $transaction_id
     */
    public function setTransactionId($transaction_id): void
    {
        $this->transaction_id = $transaction_id;
    }

    /**
     * @return mixed
     */
    public function getTransactionItemId()
    {
        return $this->transaction_item_id;
    }

    /**
     * @param mixed $transaction_item_id
     */
    public function setTransactionItemId($transaction_item_id): void
    {
        $this->transaction_item_id = $transaction_item_id;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param mixed $notes
     */
    public function setNotes($notes): void
    {
        $this->notes = $notes;
    }
}

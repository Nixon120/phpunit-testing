<?php
namespace Entities;

use Entities\Interfaces\Validateable;
use Entities\Traits\ReferenceTrait;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class TransactionItem extends Base implements Validateable
{
    use ReferenceTrait;

    public $transaction_id;

    public $guid;

    public $quantity;

    public $reissue_date;

    private $returned = 0;

    public function setTransactionId($id)
    {
        $this->transaction_id = $id;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @return mixed
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param mixed $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    public function setQuantity($qty)
    {
        $this->quantity = $qty;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return mixed
     */
    public function getReissueDate()
    {
        return $this->reissue_date;
    }

    /**
     * @param mixed $reissue_date
     */
    public function setReissueDate($reissue_date): void
    {
        $this->reissue_date = $reissue_date;
    }

    /**
     * @return bool
     */
    public function isReturned(): bool
    {
        return (int)$this->returned === 1;
    }

    /**
     * @param int $returned
     */
    public function setReturned(int $returned): void
    {
        $this->returned = $returned;
    }

    public function isValid(): bool
    {
        try {
            $this->getValidator()->assert((object) $this->toArray());
            return true;
        } catch (NestedValidationException $exception) {
            return false;
        }
    }

    public function getValidationErrors(): array
    {
        try {
            $this->getValidator()->assert((object) $this->toArray());
            return [];
        } catch (NestedValidationException $exception) {
            return $exception->getMessages();
        }
    }

    /**
     * @return Validator
     * @throws \Exception if called and stubbed method not replaced
     */
    public function getValidator()
    {
        return Validator::attribute('quantity', Validator::numeric()->setName('Quantity'));
    }
}

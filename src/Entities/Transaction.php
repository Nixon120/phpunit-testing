<?php
namespace Entities;

use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class Transaction extends Base
{
    use TimestampTrait;
    use StatusTrait;

    public $participant_id;

    public $unique_id;

    public $wholesale = 0.00;

    public $subtotal = 0.00;

    public $total = 0.00;

    public $email_address;

    public $type;

    public $verified;

    public $completed;

    public $processed;

    public $notes;

    public $shipping_reference;

    private $meta;

    /**
     * @var array|null
     */
    private $items;

    /**
     * @var Participant|null
     */
    private $participant;

    /**
     * @var Address|null
     */
    private $shipping;

    public function __construct(?Participant $participant = null)
    {
        parent::__construct();

        if (!is_null($participant)) {
            $this->setParticipantId($participant->getId());
            $this->setParticipant($participant);
        }
    }

    /**
     * @return mixed
     */
    public function getParticipantId()
    {
        return $this->participant_id;
    }

    /**
     * @param mixed $participant_id
     */
    public function setParticipantId($participant_id)
    {
        $this->participant_id = $participant_id;
    }

    public function getParticipant():?Participant
    {
        return $this->participant;
    }

    /**
     * @param Participant|null $participant
     */
    public function setParticipant(?Participant $participant)
    {
        $this->participant = $participant;
    }


    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * @param mixed $unique_id
     */
    public function setUniqueId($unique_id)
    {
        $this->unique_id = $unique_id;
    }

    /**
     * @return mixed
     */
    public function getWholesale()
    {
        return $this->wholesale;
    }

    /**
     * @param mixed $wholesale
     */
    public function setWholesale($wholesale)
    {
        $this->wholesale = $wholesale;
    }

    /**
     * @return mixed
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * @param mixed $subtotal
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }
    
    /**
     * @return mixed
     */
    public function getPointTotal()
    {
        $point = $this->getParticipant()->getProgram()->getPoint();
        return bcmul($this->total, $point, 2);
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = number_format($total, 2, '.', '');
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
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    public function hasShippingAddress()
    {
        if ($this->shipping instanceof Address) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getShipping():?Address
    {
        //@TODO implement fetching of reference
        return $this->shipping;
    }

    /**
     * @param mixed $shipping
     */
    public function setShipping(array $shipping)
    {
        $address = new Address();
        $address->hydrate($shipping);
        $address->setParticipantId($this->getParticipant()->getId());
        $this->shipping = $address;
        $this->setShippingReference($address->getReferenceId());
    }

    public function getShippingReference():?string
    {
        return $this->shipping_reference;
    }

    public function setShippingReference(string $ref)
    {
        $this->shipping_reference = $ref;
    }

    private function updatePricing(TransactionProduct $product, int $quantity)
    {
        $wholesale = bcmul($product->getWholesale(), $quantity, 2);
        $subtotal = bcmul($product->getRetail(), $quantity, 2);
        $total = bcmul($product->getPrice(), $quantity, 2);

        $this->setWholesale(bcadd($this->getWholesale(), $wholesale, 2));
        $this->setSubtotal(bcadd($this->getSubtotal(), $subtotal, 2));
        $this->setTotal(bcadd($this->getTotal(), $total, 2));
    }

    /**
     * @return TransactionItem[]
     */
    public function getItems()
    {
        $itemContainer = [];
        foreach ($this->items as $item) {
            $itemContainer[] = $item['item'];
        }

        return $itemContainer;
    }

    //set a transactionitem,
    // which adds a product reference and quantity.
    public function setItem(TransactionItem $item, TransactionProduct $product)
    {
        $this->items[] = [
            'item' => $item,
            'product' => $product
        ];
        $this->updatePricing($product, $item->getQuantity());
    }

    /**
     * Queries transaction product by reference_id (item <> product)
     * @param $reference
     * @return TransactionProduct|null
     */
    public function getProduct($reference)
    {
        foreach ($this->items as $item) {
            /** @var TransactionProduct $product */
            $product = $item['product'];
            if ($product->getReferenceId() === $reference) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getProducts():?array
    {
        $productContainer = [];
        foreach ($this->items as $item) {
            $productContainer[] = $item['product'];
        }

        return $productContainer;
    }

    /**
     * @return mixed
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * @param mixed $email_address
     */
    public function setEmailAddress($email_address)
    {
        $this->email_address = $email_address;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * @param mixed $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }

    /**
     * @return mixed
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param mixed $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return mixed
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @param mixed $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        $container = [];

        if ($this->meta !== null) {
            foreach ($this->meta as $key => $meta) {
                $container[] = [$key => $meta];
            }
        }
        return $container;
    }

    /**
     * @param mixed $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }
}

<?php
namespace Entities;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\Traits\ProgramTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;
use Services\Scheduler\Tasks\ScheduledRedemption;

class OneTimeAutoRedemption extends Base
{
    use TimestampTrait;
    use StatusTrait;
    use ProgramTrait;

    public $product_sku;

    private $product;

    public $redemption_date;

    public $all_participant;

    public function __construct(array $data = null)
    {
        parent::__construct();
        if (!is_null($data)) {
            $this->exchange($data);
        }
    }

    public function getProductSku()
    {
        return $this->product_sku;
    }

    public function setProductSku(string $sku)
    {
        $this->product_sku = $sku;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getAllParticipant()
    {
        return $this->all_participant;
    }

    /**
     * @param mixed $all_participant
     */
    public function setAllParticipant($all_participant)
    {
        $this->all_participant = $all_participant;
    }

    /**
     * @return string
     */
    public function getRedemptionDate()
    {
        return $this->redemption_date;
    }

    /**
     * @param string
     */
    public function setRedemptionDate($redemption_date)
    {
        if ($redemption_date) {
            $redemption_date = new \DateTime($redemption_date);
            $this->redemption_date = $redemption_date->format('Y-m-d');
        }
    }

    public function getTask(): ScheduledRedemption
    {
        return new ScheduledRedemption();
    }
}

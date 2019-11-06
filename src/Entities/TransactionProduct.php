<?php
namespace Entities;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\Interfaces\Validateable;
use Entities\Traits\ReferenceTrait;
use Entities\Traits\TimestampTrait;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class TransactionProduct extends Base implements Validateable
{
    use ReferenceTrait;
    use TimestampTrait;

    public $unique_id;

    public $wholesale;

    public $retail;

    public $shipping;

    public $handling;

    public $vendor_code;

    public $kg;

    public $name;

    public $description;

    public $terms;

    public $type;

    public $category;

    private $guid;

    private $quantity;

    private $reissue_date;

    /**
     * TransactionProduct constructor.
     * @param Product $product
     * @param int $amount
     */
    public function __construct($product = null, $amount = 0)
    {
        parent::__construct();

        if ($product !== null) {
            $this->exchange([
                'unique_id' => $product->getSku(),
                'wholesale' => $product->getPriceWholesale(),
                'retail' => $product->getPriceRetail(),
                'handling' => $product->getPriceHandling(),
                'shipping' => $product->getPriceShipping(),
                'vendor_code' => $product->getSku(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'terms' => $product->getTerms(),
                'type' => $product->isDigital()?1:0,
                'category' => $product->getCategory() !== null ? $product->getCategory()->getName() : "Other"
            ]);
            if ($product instanceof Product && $product->isPriceRanged()) {
                $this->setRetail($amount);
            }

            $this->generateReference();
        }
    }

    private function generateReference()
    {
        $exchange = [
            'unique_id' => $this->getUniqueId(),
            'wholesale' => $this->getWholesale(),
            'retail' => $this->getRetail(),
            'handling' => $this->getHandling(),
            'vendor_code' => $this->getVendorCode(),
            'kg' => $this->getKg(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'terms' => $this->getTerms(),
            'type' => $this->getType()
        ];

        $reference = sha1(json_encode($exchange));
        $this->setReferenceId($reference);
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

    public function getPrice()
    {
        $price = bcadd($this->retail, $this->shipping, 2);
        $price = bcadd($price, $this->handling, 2);

        return $price;
    }

    /**
     * @return mixed
     */
    public function getRetail()
    {
        return $this->retail;
    }

    /**
     * @param mixed $retail
     */
    public function setRetail($retail)
    {
        $this->retail = $retail;
    }

    /**
     * @return mixed
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param mixed $shipping
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * @return mixed
     */
    public function getHandling()
    {
        return $this->handling;
    }

    /**
     * @param mixed $handling
     */
    public function setHandling($handling)
    {
        $this->handling = $handling;
    }

    /**
     * @return mixed
     */
    public function getVendorCode()
    {
        return $this->vendor_code;
    }

    /**
     * @param mixed $vendor_code
     */
    public function setVendorCode($vendor_code)
    {
        $this->vendor_code = $vendor_code;
    }

    /**
     * @return mixed
     */
    public function getKg()
    {
        return $this->kg;
    }

    /**
     * @param mixed $kg
     */
    public function setKg($kg)
    {
        $this->kg = $kg;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * @param mixed $terms
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
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
     * @param $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function getQuantity()
    {
        return $this->quantity;
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

    public function isValid(): bool
    {
        try {
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
    }
}

<?php
namespace Controllers\Product;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Controllers\AbstractOutputNormalizer;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function get(): array
    {
        /** @var Product $product */
        $product = parent::get();
        $return = $this->scrub($product->toArray(), []);
        return $return;
    }

    public function getList(): array
    {
        $list = parent::get();

        $return = $this->scrubList($list, []);

        return $return;
    }
}

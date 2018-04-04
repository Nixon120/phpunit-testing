<?php
namespace Services\Product;

use AllDigitalRewards\Services\Catalog\Entity\Brand;
use AllDigitalRewards\Services\Catalog\Entity\Product;
use AllDigitalRewards\Services\Catalog\Filter\Filter;
use Controllers\Interfaces as Interfaces;
use Repositories\ProductRepository;

class ProductRead
{
    /**
     * @var ProductRepository
     */
    public $repository;

    public function __construct(
        ProductRepository $repository
    ) {
        $this->repository = $repository;
    }

    //@TODO Change to getBySku
    public function getById($sku): ?Product
    {
        $product = $this->repository->getProductBySku($sku);

        if ($product) {
            return $product;
        }

        return null;
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filterInput = $input->getInput();
        unset($filterInput['method']);
        $filterNormalizer = new ProductFilterNormalizer($filterInput);
        // Let's remove non-relevant items for filter
        $filter = new Filter($filterNormalizer->getFilters());
        $products = $this->repository->getProductCollection($filter, $input->getPage());
        return $products;
    }

    public function getProductSearchList(Interfaces\InputNormalizer $input)
    {
        $products = $this->get($input);
        $productContainer = [];

        $key = 0;
        while ($products) {
            /** @var Product $product */
            $product = $products[$key];
            $productContainer[] = [
                'sku' => $product->getSku(),
                'name' => implode(' ', [$product->getName(), '[<strong>' . $product->getSku() . '</strong>]']),
                'name_normal' => $product->getName(),
                'price_wholesale' => $product->getPriceWholesale(),
                'price_retail' => $product->getPriceRetail(),
                'price_shipping' => $product->getPriceShipping(),
                'price_handling' => $product->getPriceHandling(),
                'price_total' => $product->getPriceTotal()
            ];

            unset($products[$key]);
            $key++;
        }

        return $productContainer;
    }

    public function getCategories(?array $get = null)
    {
        $categories = $this->repository->getCategoryCollection();
        if ($get !== null && !empty($categories)) {
            if (!empty($get['name'])) {
                $categories = $this->filterList($categories, $get);
            }
        }
        return $categories;
    }

    public function getBrands(?array $get = null)
    {
        $brands = $this->repository->getBrandCollection();
        if ($get !== null && !empty($brands)) {
            if (!empty($get['name'])) {
                $brands = $this->filterList($brands, $get);
            }
        }

        return $brands;
    }

    private function filterList(array $list, array $get)
    {
        foreach ($list as $key => $item) {
            /** @var Category|Brand $item */
            if (strpos(strtolower($item->getName()), strtolower($get['name'])) === false) {
                unset($list[$key]);
            }
        }

        return array_values($list);
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}

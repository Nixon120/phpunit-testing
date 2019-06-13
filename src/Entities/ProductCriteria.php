<?php
namespace Entities;

use AllDigitalRewards\Services\Catalog\Entity\Brand;
use AllDigitalRewards\Services\Catalog\Entity\Category;
use Entities\Traits\TimestampTrait;

class ProductCriteria extends Base
{
    use TimestampTrait;

    public $program_id;

    public $filter;

    public $featured_page_title;

    private $categories;

    private $brands;

    private $products;

    private $exclude_products;

    private $exclude_brands;

    private $exclude_vendors;

    private $minFilter;

    private $maxFilter;

    private $productFilter;

    private $categoryFilter;

    private $excludeProductsFilter;

    private $excludeBrandsFilter;

    private $excludeVendorsFilter;

    private $brandFilter;

    /**
     * @return mixed
     */
    public function getProgramId()
    {
        return $this->program_id;
    }

    /**
     * @param mixed $programId
     */
    public function setProgramId($programId)
    {
        $this->program_id = $programId;
    }

    /**
     * @return null|string
     */
    public function getFeaturedPageTitle(): ?string
    {
        return $this->featured_page_title;
    }

    /**
     * @param null|string $featuredPageTitle
     */
    public function setFeaturedPageTitle(?string $featuredPageTitle)
    {
        $this->featured_page_title = $featuredPageTitle;
    }

    /**
     * @return Category[]|null
     */
    public function getCategories():?array
    {
        return $this->categories;
    }

    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return Brand[]|null
     */
    public function getBrands():?array
    {
        return $this->brands;
    }

    public function setBrands(array $brands)
    {
        $this->brands = $brands;
    }

    /**
     * @return Product[]|null
     */
    public function getProducts(): ?array
    {
        return $this->products;
    }

    public function setProducts(array $products)
    {
        $this->products = $products;
    }

    /**
     * @return Product[]|null
     */
    public function getExcludeProducts(): ?array
    {
        return $this->exclude_products;
    }

    public function setExcludeProducts(array $products)
    {
        $this->exclude_products = $products;
    }

    /**
     * @return Brand[]
     */
    public function getExcludeBrands(): ?array
    {
        return $this->exclude_brands;
    }

    /**
     * @param mixed $exclude_brands
     */
    public function setExcludeBrands(array $exclude_brands)
    {
        $this->exclude_brands = $exclude_brands;
    }

    /**
     * @return array
     */
    public function getExcludeVendors(): ?array
    {
        return $this->exclude_vendors;
    }

    /**
     * @param array $exclude_vendors
     */
    public function setExcludeVendors(array $exclude_vendors)
    {
        $this->exclude_vendors = $exclude_vendors;
    }

    /**
     * @return mixed
     */
    public function getExcludeBrandsFilter()
    {
        return $this->excludeBrandsFilter;
    }

    /**
     * @param mixed $excludeBrandsFilter
     */
    public function setExcludeBrandsFilter($excludeBrandsFilter)
    {
        $this->excludeBrandsFilter = $excludeBrandsFilter;
    }

    /**
     * @return mixed
     */
    public function getExcludeVendorsFilter()
    {
        return $this->excludeVendorsFilter;
    }

    /**
     * @param mixed $excludeVendorsFilter
     */
    public function setExcludeVendorsFilter($excludeVendorsFilter)
    {
        $this->excludeVendorsFilter = $excludeVendorsFilter;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    public function getFilterArray()
    {
        return json_decode($this->filter, true);
    }

    /**
     * @param array|string $filters
     */
    public function setFilter($filters)
    {
        if (is_array($filters)) {
            $filters = $this->formatFilter($filters);
        }
        $this->filter = $filters;
        $filters = json_decode($filters);
        if (empty($filters)) {
            //throw some exception/ return false
        }
        $this->setMinFilter($filters->price->min);
        $this->setMaxFilter($filters->price->max);
        $this->setProductFilter($filters->products);
        $this->setCategoryFilter($filters->category);
        $this->setBrandFilter($filters->brand);

        if (!empty($filters->exclude_products)) {
            $this->setExcludeProductsFilter($filters->exclude_products);
        }
        if (!empty($filters->exclude_brands)) {
            $this->setExcludeBrandsFilter($filters->exclude_brands);
        }
        if (!empty($filters->exclude_vendors)) {
            $this->setExcludeVendorsFilter($filters->exclude_vendors);
        }
    }

    /**
     * @param $min
     */
    public function setMinFilter($min)
    {
        $this->minFilter = $min;
    }

    /**
     * @return mixed
     */
    public function getMinFilter()
    {
        return $this->minFilter;
    }

    /**
     * @param $max
     */
    public function setMaxFilter($max)
    {
        $this->maxFilter = $max;
    }

    /**
     * @return mixed
     */
    public function getMaxFilter()
    {
        return $this->maxFilter;
    }

    /**
     * @return mixed
     */
    public function getProductFilter()
    {
        return $this->productFilter;
    }

    /**
     * @param $products
     */
    public function setProductFilter($products)
    {
        $this->productFilter = $products;
    }

    /**
     * @return mixed
     */
    public function getCategoryFilter()
    {
        return $this->categoryFilter;
    }

    /**
     * @param $categories
     */
    public function setCategoryFilter($categories)
    {
        $this->categoryFilter = $categories;
    }

    /**
     * @return mixed
     */
    public function getExcludeProductsFilter()
    {
        return $this->excludeProductsFilter;
    }

    /**
     * @param mixed $excludeProductsFilter
     */
    public function setExcludeProductsFilter($excludeProductsFilter)
    {
        $this->excludeProductsFilter = $excludeProductsFilter;
    }

    /**
     * @return mixed
     */
    public function getBrandFilter()
    {
        return $this->brandFilter;
    }

    /**
     * @param $brands
     */
    public function setBrandFilter($brands)
    {
        $this->brandFilter = $brands;
    }

    /**
     * Format the filter for storage
     * @param array $filters
     * @return string
     */
    private function formatFilter(array $filters)
    {
        $filter = [
            'price' => [
                'min' => '',
                'max' => ''
            ],
            'products' => [],
            'exclude_products' => [],
            'exclude_brands' => [],
            'exclude_vendors' => [],
            'category' => [],
            'brand' => []
        ];

        //need to handle undefined property error
        if (isset($filters['exclude_products']) === false) {
            $filters['exclude_products'] = [];
        }
        if (isset($filters['exclude_brands']) === false) {
            $filters['exclude_brands'] = [];
        }
        if (isset($filters['exclude_vendors']) === false) {
            $filters['exclude_vendors'] = [];
        }

        if (!empty($filters['max'])) {
            $filter['price']['max'] = $filters['max'];
        }

        if (!empty($filters['min'])) {
            $filter['price']['min'] = $filters['min'];
        }

        if (!empty($filters['products'])) {
            $filter['products'] = $filters['products'];
        }
        if (!empty($filters['exclude_products'])) {
            $filter['exclude_products'] = $filters['exclude_products'];
        }
        if (!empty($filters['exclude_brands'])) {
            $filter['exclude_brands'] = $filters['exclude_brands'];
        }
        if (!empty($filters['exclude_vendors'])) {
            $filter['exclude_vendors'] = $filters['exclude_vendors'];
        }
        if (!empty($filters['categories'])) {
            $filter['category'] = $filters['categories'];
        }
        if (!empty($filters['brands'])) {
            $filter['brand'] = $filters['brands'];
        }

        return json_encode($filter);
    }
}

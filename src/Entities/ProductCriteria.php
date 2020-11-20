<?php
namespace Entities;

use AllDigitalRewards\Services\Catalog\Entity\Brand;
use AllDigitalRewards\Services\Catalog\Entity\Category;
use AllDigitalRewards\Services\Catalog\Entity\Group;
use Entities\Traits\TimestampTrait;

class ProductCriteria extends Base
{
    use TimestampTrait;

    public $program_id;

    public $filter;

    public $featured_page_title;

    private $categories;

    private $brands;

    private $groups;

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

    private $groupFilter;

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
     * @return string
     */
    public function getFeaturedPageTitle()
    {
        if($this->featured_page_title === null) {
            return '';
        }

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
     * @return Group[]|null
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups(array $groups)
    {
        $this->groups = $groups;
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
        if (empty($filters) === true) {
            $filters = [];
        }
        $filters = $this->formatFilter($filters);
        $this->filter = $filters;
        $filters = json_decode($filters);

        $this->setMinFilter($filters->price->min);
        $this->setMaxFilter($filters->price->max);
        $this->setProductFilter($filters->products);
        $this->setCategoryFilter($filters->category);
        $this->setBrandFilter($filters->brand);
        $this->setGroupFilter($filters->group);

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

    public function getGroupFilter()
    {
        return $this->groupFilter;
    }

    /**
     * @param $groups
     */
    public function setGroupFilter($groups)
    {
        $this->groupFilter = $groups;
    }

    /**
     * Format the filter for storage
     * @param array|string $filters
     * @return string
     */
    private function formatFilter($filters)
    {
        //comes in as array or json string
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        $filter = [
            'price' => [
                'min' => $this->hydrateMinPriceFilter($filters),
                'max' => $this->hydrateMaxPriceFilter($filters)
            ],
            'products' => empty($filters['products']) === false ? $filters['products'] : [],
            'exclude_products' => empty($filters['exclude_products']) === false ? $filters['exclude_products'] : [],
            'exclude_brands' => empty($filters['exclude_brands']) === false ? $filters['exclude_brands'] : [],
            'exclude_vendors' => empty($filters['exclude_vendors']) === false ? $filters['exclude_vendors'] : [],
            'category' => $this->hydrateCategoryFilter($filters),
            'brand' => $this->hydrateBrandsFilter($filters),
            'group' => $this->hydrateGroupFilter($filters)
        ];

        return json_encode($filter);
    }

    /**
     * @param $filters
     * @return string
     */
    private function hydrateMinPriceFilter($filters)
    {
        if (empty($filters['price']) === false) {
            return $filters['price']['min'] ?? '';
        }

        if (empty($filters['min']) === false) {
            return $filters['min'];
        }

        return '';
    }

    /**
     * @param $filters
     * @return string
     */
    private function hydrateMaxPriceFilter($filters)
    {
        if (empty($filters['price']) === false) {
            return $filters['price']['max'] ?? '';
        }

        if (empty($filters['max']) === false) {
            return $filters['max'];
        }

        return '';
    }

    /**
     * @param $filters
     * @return array
     */
    private function hydrateCategoryFilter($filters): array
    {
        //stored singular in db so we check as well
        if (empty($filters['category']) === false) {
            return $filters['category'];
        }
        if (empty($filters['categories']) === false) {
            return $filters['categories'];
        }

        return [];
    }

    /**
     * @param $filters
     * @return array
     */
    private function hydrateBrandsFilter($filters): array
    {
        //stored singular in db so we check as well
        if (empty($filters['brand']) === false) {
            return $filters['brand'];
        }
        if (empty($filters['brands']) === false) {
            return $filters['brands'];
        }
        return [];
    }

    /**
     * @param $filters
     * @return array
     */
    private function hydrateGroupFilter($filters): array
    {
        //stored singular in db so we check as well
        if (empty($filters['group']) === false) {
            return $filters['group'];
        }
        if (empty($filters['groups']) === false) {
            return $filters['groups'];
        }
        return [];
    }
}

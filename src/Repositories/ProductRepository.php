<?php
namespace Repositories;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use AllDigitalRewards\Services\Catalog\Filter\Filter;
use \PDO as PDO;
use AllDigitalRewards\Services\Catalog\Client;

class ProductRepository extends BaseRepository
{
    /**
     * @var Client
     */
    private $catalog;

    public function __construct(PDO $database, Client $client)
    {
        parent::__construct($database);

        $this->catalog = $client;
    }

    /**
     * @var string When inserting or updating, this property is used for the table name.
     */
    protected $table = 'Product';

    public function getProductCollection(
        Filter $filters,
        $page = 1,
        $limit = 30
    ) {
        $productCollection = $this->catalog->getProducts($filters->getActiveFilters(), $page, $limit);
        return $productCollection;
    }

    public function getRepositoryEntity()
    {
        return Product::class;
    }

    public function getProductBySku($sku): ?Product
    {
        return $this->catalog->getProduct($sku);
    }

    public function getCategoryCollection()
    {
        $categoryCollection = $this->catalog->getCategories();
        return $categoryCollection;
    }

    public function getBrandCollection()
    {
        $brandCollection = $this->catalog->getBrands();
        return $brandCollection;
    }
}

<?php
namespace Services\Product;

use AllDigitalRewards\Services\Catalog\Client;
use Interop\Container\ContainerInterface;
use Repositories\ProductRepository;
use Services\AbstractServiceFactory;

class ServiceFactory extends AbstractServiceFactory
{
    private $productRepository;

    public function getProductRepository(): ProductRepository
    {
        if ($this->productRepository === null) {
            $this->productRepository = new ProductRepository($this->getContainer()->get('database'), $this->getCatalogService());
        }

        return $this->productRepository;
    }

    public function getProductRead(): ProductRead
    {
        return new ProductRead($this->getProductRepository());
    }
}

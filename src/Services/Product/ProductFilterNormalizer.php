<?php
namespace Services\Product;

class ProductFilterNormalizer
{
    private $filters = [];

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}

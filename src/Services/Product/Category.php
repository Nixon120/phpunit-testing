<?php
namespace Services\Product;

use Controllers\Interfaces as Interfaces;
use Repositories\CategoryRepository;

class Category
{
    /**
     * @var array
     */
    private $categories;

    /**
     * @var CategoryRepository
     */
    public $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getSingle($id): ?\Entities\Category
    {
        $product = $this->repository->getCategory($id);

        if ($product) {
            return $product;
        }

        return null;
    }

    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new CategoryFilterNormalizer($input->getInput());
        $organizations = $this->repository->getCollection($filter, $input->getOffset(), 30);
        return $organizations;
    }

    public function getFeed(array $filters = [])
    {
        if (is_null($this->categories)) {
            $page = !empty($filters['page']) ? $filters['page'] : 1;
            $offset = !empty($filters['offset']) ? $filters['offset'] : 30;
            unset($filters['offset'], $filters['page']);
            $this->categories = $this->repository->getFeedCategories($filters, $page, $offset);
        }

        return $this->categories;
    }

    public function setCategoryRelation($id, $post)
    {
        $success = true;
        if (!empty($post['add_categories'])) {
            $success = $this->repository->addCategoryLinks($id, $post['add_categories']);
        }
        if (!empty($post['remove_categories'])) {
            $success = $this->repository->removeCategoryLinks($post['remove_categories']);
        }

        return $success;
    }

    public function insert($data):bool
    {
        $category = new \Entities\Category;
        $category->exchange($data);
        if ($this->repository->validate($category) && $this->repository->insert($category->toArray())) {
            return true;
        }
        return false;
    }

    public function update($id, $data):bool
    {
        $category = $this->getSingle($id);
        $category->exchange($data);

        if ($this->repository->validate($category) && $this->repository->update($id, $data)) {
            return true;
        }
        return false;
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}

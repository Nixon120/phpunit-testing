<?php
namespace Services\Organization;

use Interop\Container\ContainerInterface;
use Repositories\DomainRepository;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class Domain
{
    /**
     * @var DomainRepository
     */
    public $repository;

    public function __construct(
        DomainRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function getSingle($id): ?\Entities\Domain
    {
        $domain = $this->repository->getDomain($id);

        if ($domain) {
            return $domain;
        }

        return null;
    }

    public function get(array $filters = [])
    {
        return $this->repository->getDomains($filters);
    }
}

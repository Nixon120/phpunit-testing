<?php

namespace Services\User;

use Controllers\Interfaces as Interfaces;
use Entities\User;
use Repositories\UserRepository;

class UserRead
{
    /**
     * @var UserRepository
     */
    public $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getById($id): ?\Entities\User
    {
        $user = $this->repository->getUserById($id);

        if ($user) {
            return $user;
        }

        return null;
    }

    public function getByEmail($email): ?\Entities\User
    {
        $user = $this->repository->getUserByEmail($email);

        if ($user) {
            return $user;
        }

        return null;
    }

    public function getByInviteToken($token)
    {
        return $this->repository->getUserByInviteToken($token);
    }

    /**
     * @param Interfaces\InputNormalizer $input
     * @return User[]|null
     */
    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new FilterNormalizer($input->getInput());
        $users = $this
            ->repository
            ->getCollection(
                $filter,
                $input->getPage(),
                $input->getLimit()
            );
        return $users;
    }

    /**
     * @return array|null
     */
    public function getApiAuthUserContainer()
    {
        return $this->repository->getAuthUsers();
    }
}

<?php

namespace Services\User;

class UserModify
{
    /**
     * @var ServiceFactory
     */
    public $factory;

    public function __construct(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $data
     * @return false|\Entities\User
     */
    public function insert($data)
    {
        if (!empty($data['organization'])) {
            $organization = $this
                ->factory
                ->getUserRepository()
                ->getUserOrganization(
                    $data['organization'],
                    true
                );

            $data['organization_id'] = $organization->getId();
            unset($data['organization']);
        }

        $user = new \Entities\User;
        $user->exchange($data);

        $isUnique = $this
            ->factory
            ->getUserRepository()
            ->isUserEmailUnique(
                $user->getEmailAddress()
            );

        if ($isUnique === false) {
            $this->factory->getUserRepository()->setErrors([
                'The email ' . $user->getEmailAddress() . ' is already assigned to another user.'
            ]);

            return false;
        }

        if ($this->factory->getUserRepository()->validate($user)
            && $this->factory->getUserRepository()->insert($user->toArray())) {
            $userId = $this->factory->getUserRepository()->getLastInsertId();
            return $this->factory->getUserRepository()->getUserById($userId);
        }

        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return false|\Entities\User
     */
    public function update($id, $data)
    {
        if (!empty($data['organization'])) {
            $organization = $this
                ->factory
                ->getUserRepository()
                ->getUserOrganization(
                    $data['organization'],
                    true
                );

            $data['organization_id'] = $organization->getId();
        }

        if (!empty($data['password'])) {
            $password = $data['password'];
        }

        unset($data['organization'], $data['password']);

        $user = $this->factory->getUserRead()->getById($id);
        $oldEmail = $user->getEmailAddress();
        $user->exchange($data);


        $isUnique = $this
            ->factory
            ->getUserRepository()
            ->isUserEmailUnique($user->getEmailAddress());

        if ($oldEmail !== $user->getEmailAddress()
            && $isUnique === false
        ) {
            // unique_id has already been assigned to another Organization.

            $this->factory->getUserRepository()->setErrors([
                'The email ' . $user->getEmailAddress() . ' is already assigned to another user.'
            ]);

            return false;
        }

        if (!empty($password)) {
            $data['password'] = $password;
            $data = $this->hydratePassword($data, $user);
        }

        if ($this->factory->getUserRepository()->validate($user)
            && $this->factory->getUserRepository()->update($user->getId(), $data)) {
            return $this->factory->getUserRepository()->getUserById($user->getId());
        }

        return false;
    }

    private function hydratePassword($data, \Entities\User $user)
    {
        $password = $data['password'];
        unset($data['password']);
        if (!password_verify($password, $user->getPassword()) && $password !== "") {
            $user->setPassword($password);
            $data['password'] = $user->getPassword();
        } else {
            // We're going to ignore this on update
            $this->factory->getUserRepository()->setSkip(['password']);
        }

        return $data;
    }

    public function getErrors()
    {
        return $this
            ->factory
            ->getUserRepository()
            ->getErrors();
    }
}

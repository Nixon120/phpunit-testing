<?php

namespace Services\User;

use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use Entities\User;

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
     * @return false|User
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

        $user = new User;
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
     * @return false|User
     */
    public function update($id, $data)
    {
        if (empty($data['organization']) === false) {
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
        $originalAccessLevel = $user->getAccessLevel();
        $user->exchange($data);

        $isUnique = $this
            ->factory
            ->getUserRepository()
            ->isUserEmailUnique($user->getEmailAddress());

        if ($oldEmail !== $user->getEmailAddress() && $isUnique === false) {
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
            if ($this->isUserAccessLevelUpdatedToPiiLimit($originalAccessLevel, $user) === true) {
                //hit report api and delete reports with oldemail
            }
            return $this->factory->getUserRepository()->getUserById($user->getId());
        }

        return false;
    }

    private function hydratePassword($data, User $user)
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

    /**
     * @param int $originalAccessLevel
     * @param User|null $user
     */
    private function isUserAccessLevelUpdatedToPiiLimit(int $originalAccessLevel, ?User $user): bool
    {
       return $originalAccessLevel !== $user->getAccessLevel()
           && UserAccessLevelEnum::PII_LIMIT === $user->getAccessLevel();
    }
}

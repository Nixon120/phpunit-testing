<?php

namespace Services\User;

use AllDigitalRewards\RewardStack\Services\ReportApiService;
use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use DateTime;
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
            $this->deleteRecurringReportsIfUserAccessLevelUpdatedToPiiLimit(
                $originalAccessLevel,
                $user,
                $oldEmail
            );
            return $this->factory->getUserRepository()->getUserById($user->getId());
        }

        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return false|User
     */
    public function patch($id, $data)
    {
        $user = $this->factory->getUserRead()->getById($id);

        if (!$user instanceof User) {
            $this->factory->getUserRepository()->setErrors(
                [
                    'Password and password confirmation did not match'
                ]
            );
            return false;
        }

        //If confirm is present we assume its a password update from UI
        if (!empty($data['password'])) {
            $password = $data['password'];
            if (password_verify($password, $user->getPassword())) {
                $this->factory->getUserRepository()->setErrors(
                    [
                        'New password must be different than current password'
                    ]
                );
                return false;
            }
            $user->setPassword($password);
            $input['password_updated_at'] = (new DateTime())->format('Y-m-d H:i:s');
            $input['password'] = $user->getPassword();
            if ($this->factory->getUserRepository()->validate($user)
                && $this->factory->getUserRepository()->update($user->getId(), $input)) {
                return $this->factory->getUserRepository()->getUserById($user->getId());
            }

            return false;
        }

        return $this->factory->getUserRepository()->getUserById($user->getId());
    }

    private function hydratePassword($data, User $user)
    {
        $password = $data['password'];
        unset($data['password']);
        if (!password_verify($password, $user->getPassword()) && $password !== "") {
            $user->setPassword($password);
            $data['password'] = $user->getPassword();
            //UI is checking and forcing an update as well
            $data['password_updated_at'] = (new DateTime())->format('Y-m-d H:i:s');
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
     * @param $oldEmail
     */
    private function deleteRecurringReportsIfUserAccessLevelUpdatedToPiiLimit(
        int $originalAccessLevel,
        ?User $user,
        $oldEmail
    ) {
        if (empty($oldEmail) === false
            && $originalAccessLevel !== $user->getAccessLevel()
            && UserAccessLevelEnum::PII_LIMIT === $user->getAccessLevel()
        ) {
            $token = $this->factory->getAuthenticatedTokenString();
            (new ReportApiService($token))->removeUserReports($oldEmail);
        }
    }
}

<?php

namespace Services\User;

use Repositories\UserRepository;
use Services\AbstractServiceFactory;
use Services\Email\EmailPublisherFactory;

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var UserRecovery
     */
    private $userRecoveryService;

    public function getUserRepository(): UserRepository
    {
        if ($this->userRepository === null) {
            $user = $this->getAuthenticatedUser();
            $this->userRepository = new UserRepository($this->getDatabase());
            if ($user !== null) {
                $this->userRepository->setProgramIdContainer($user->getProgramOwnershipIdentificationCollection());
                $this->userRepository->setOrganizationIdContainer($user->getOrganizationOwnershipIdentificationCollection());
            }
        }

        return $this->userRepository;
    }

    public function getUserRead(): UserRead
    {
        //@TODO let's swap this up ? Maybe have a whole service for transaction ? Makes sense.. maybe
        return new UserRead($this->getUserRepository());
    }

    public function getUserModify(): UserModify
    {
        return new UserModify($this);
    }

    public function getUserInvite(): UserInvite
    {
        return new UserInvite($this);
    }

    public function getUserRecovery(): UserRecovery
    {
        if ($this->userRecoveryService === null) {
            $this->userRecoveryService = new UserRecovery($this);
        }

        return $this->userRecoveryService;
    }

    public function getEmailPublisherService()
    {
        $emailPublisherServiceFactory = new EmailPublisherFactory($this->getContainer());
        return $emailPublisherServiceFactory();
    }
}

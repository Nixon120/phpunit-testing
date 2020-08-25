<?php

namespace Services\User;

use Entities\Email;
use Entities\Organization;
use Entities\User;
use Slim\Views\PhpRenderer;

class UserRecovery
{
    /**
     * @var ServiceFactory
     */
    public $factory;
    /**
     * @var array
     */
    private $errors = [];

    public function __construct(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function sendRecoveryEmail(User $user): bool
    {
        /** @var Organization|null $organization */
        $organization = $user->getOrganization();
        $result = $this->factory->getUserModify()->update($user->getId(), [
            'invite_token' => bin2hex(random_bytes(64)),
            'organization' => $organization !== null ? $organization->getUniqueId() : null
        ]);
        if (!$result instanceof User) {
            $this->errors = $this->factory->getUserModify()->getErrors();
            return false;
        }

        $user = $this->factory->getUserRead()->getById($user->getId());

        $email = $this->generateEmail($user);

        $this
            ->factory
            ->getEmailPublisherService()
            ->publishJson($email);

        return true;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function generateEmail(User $user)
    {
        /** @var PhpRenderer $renderer */
        $renderer = $this->factory->getContainer()->get('renderer');
        $emailBody = $renderer->fetch(
            'user/recovery/recovery-email.phtml',
            ['user' => $user]
        );

        return new Email(
            $user->getEmailAddress(),
            'csr@alldigitalrewards.com',
            'Account Recovery',
            $emailBody
        );
    }
}

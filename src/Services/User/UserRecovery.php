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

    public function __construct(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    public function sendRecoveryEmail(User $user)
    {
        /** @var Organization|null $organization */
        $organization = $user->getOrganization();
        $this->factory->getUserModify()->update($user->getId(), [
            'invite_token' => bin2hex(random_bytes(64)),
            'organization' => $organization !== null ? $organization->getId() : null
        ]);

        $user = $this->factory->getUserRead()->getById($user->getId());

        $email = $this->generateEmail($user);

        $this
            ->factory
            ->getEmailPublisherService()
            ->publishJson($email);
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

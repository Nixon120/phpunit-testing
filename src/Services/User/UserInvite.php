<?php

namespace Services\User;

use Entities\Email;
use Entities\User;
use Slim\Views\PhpRenderer;

class UserInvite
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
    public function invite($data)
    {
        $organization_name = '';

        if (!empty($data['organization'])) {
            $organization = $this
                ->factory
                ->getUserRepository()
                ->getUserOrganization(
                    $data['organization'],
                    true
                );

            $data['organization_id'] = $organization->getId();
            $organization_name  = $organization->getName();
            unset($data['organization']);
        }

        $user = new \Entities\User;
        $user->exchange($data);
        $user->setInviteToken(bin2hex(random_bytes(64)));
        $user->setActive(0);

        $isUnique = $this
            ->factory
            ->getUserRepository()
            ->isUserEmailUnique(
                $user->getEmailAddress()
            );

        if ($isUnique === false) {
            $this
                ->factory
                ->getUserRepository()
                ->setErrors([
                    'The email ' . $user->getEmailAddress() . ' is already assigned to another user.'
                ]);

            return false;
        }

        $createUserSuccess = $this
            ->factory
            ->getUserRepository()
            ->insert($user->toArray());

        if ($createUserSuccess === false) {
            return false;
        }

        $userId = $this
            ->factory
            ->getUserRepository()
            ->getLastInsertId();

        $savedUser = $this
            ->factory
            ->getUserRepository()
            ->getUserById($userId);

        $this->sendInviteEmail($savedUser, $organization_name);

        // Lets pretend nothing exploded.
        return true;
    }

    private function sendInviteEmail(User $user, $organization_name)
    {
        $email = $this->generateEmail($user, $organization_name);

        $this
            ->factory
            ->getEmailPublisherService()
            ->publishJson($email);
    }

    private function generateEmail(User $user, $organization_name)
    {
        /** @var PhpRenderer $renderer */
        $renderer = $this->factory->getContainer()->get('renderer');
        $emailBody = $renderer->fetch(
            'user/invite/invite-email.phtml',
            [
                'user' => $user,
                'organization_name' => $organization_name
            ]
        );

        return new Email(
            $user->getEmailAddress(),
            'csr@alldigitalrewards.com',
            'You\'re Invited to join Sharecare\'s Marketplace Team',
            $emailBody
        );
    }
}

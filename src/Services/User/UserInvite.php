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

        $this->sendInviteEmail($savedUser);

        // Lets pretend nothing exploded.
        return true;
    }

    private function sendInviteEmail(User $user)
    {
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
            'user/invite/invite-email.phtml',
            ['user' => $user]
        );

        return new Email(
            $user->getEmailAddress(),
            'csr@alldigitalrewards.com',
            'You\'re Invited to join '. $user->getOrganization()->getName().' Team',
            $emailBody
        );
    }
}

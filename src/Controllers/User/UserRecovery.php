<?php

namespace Controllers\User;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\User\ServiceFactory;
use Slim\Views\PhpRenderer;

class UserRecovery extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
    
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    // The first step in the recovery process
    public function renderRecoveryForm()
    {
        return $this->render(
            $this->getRenderer()->fetch(
                'user/recovery/recovery-form.phtml'
            ),
            'empty.phtml'
        );
    }

    //This method is executed after they supply their email in step 1
    public function submitRecoveryForm()
    {
        $email = $this->request->getParsedBody()['email_address'] ?? null;
        if ($email === null || !$user = $this->factory->getUserRead()->getByEmail($email)) {
            $this->factory->getFlashMessenger()
                ->addMessage('warning', 'Sorry, invalid email and/or password.');

            return $this->response->withRedirect(
                '/user/recovery',
                302
            );
            //redirect with error
        }

        $this->factory->getUserRecovery()->sendRecoveryEmail($user);

        $this->factory->getFlashMessenger()
            ->addMessage('success', 'An email with instructions to recover your password has been sent.');

        return $this->response->withRedirect(
            '/login',
            302
        );
    }

    // After being emailed, they are directed here
    public function renderPasswordRecoveryForm($token)
    {
        if (!$user = $this->factory->getUserRead()->getByInviteToken($token)) {
            $this->factory->getFlashMessenger()
                ->addMessage('warning', 'There was a problem with your request. Please try again, from the beginning');

            return $this->response->withRedirect(
                '/user/recovery',
                302
            );
            //redirect with error
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'user/recovery/recovery-password.phtml',
                ['user' => $user]
            ),
            'empty.phtml'
        );
    }

    // This method handles directing traffic for the password change request
    public function submitRecoveryPassword($token)
    {
        if (!$user = $this->factory->getUserRead()->getByInviteToken($token)) {
            return $this->redirectTokenNotFound();
        }

        $password = $this->request->getParsedBody()['password'] ?? null;
        $confirm = $this->request->getParsedBody()['confirm'] ?? null;

        if ($password !== $confirm) {
            //Passwords don't match.. We can move this to middleware, sorta a cheap implementation here
            $this->factory->getFlashMessenger()
                ->addMessage('warning', 'Password and password confirmation did not match');

            return $this->response->withRedirect(
                '/user/recovery/' . $token,
                302
            );
        }

        $this->factory->getUserModify()
            ->update($user->getId(), [
                'password' => $password,
                'invite_token' => ''
            ]);

        //Woot
        return $this->redirectSuccessfulPasswordChangeRequest();
    }

    private function redirectSuccessfulPasswordChangeRequest()
    {
        $this->factory->getFlashMessenger()
            ->addMessage('success', 'Password has been updated, you may login with your new password below');

        return $this->response->withRedirect(
            '/login',
            302
        );
    }

    private function redirectTokenNotFound()
    {
        //Token is invalid
        $this->factory->getFlashMessenger()
            ->addMessage('warning', 'There was a problem with your request. Please try again, from the beginning');

        return $this->response->withRedirect(
            '/user/recovery',
            302
        );
    }
}

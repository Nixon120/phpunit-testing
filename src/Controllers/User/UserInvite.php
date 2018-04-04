<?php

namespace Controllers\User;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Authentication\Authenticate;
use Services\User\ServiceFactory;
use Services\User\UserModify;
use Slim\Views\PhpRenderer;

class UserInvite extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;
    /**
     * @var UserModify
     */
    private $userModifyService;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderInviteForm()
    {
        $query_params = $this->request->getQueryParams();
        if (empty($query_params['token'])) {
            // Token not provided.
            return $this->redirectToLogin();
        }

        $user = $this
            ->factory
            ->getUserRead()
            ->getByInviteToken($query_params['token']);

        if (!$user) {
            // No user found with token.
            return $this->redirectToLogin();
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'user/invite-form.phtml',
                ['user' => $user]
            ),
            'empty.phtml'
        );
    }

    public function registerUser()
    {
        $userData = $this->request->getParsedBody();

        $user = $this
            ->factory
            ->getUserRead()
            ->getByInviteToken($userData['invite_token']);

        if (!$user) {
            // Bad Token - we should log this.
            return $this->redirectToLogin();
        }

        if (empty($userData['password'])) {
            return $this->failWithErrors($user, ['Password must not be blank.']);
        }

        $userData['organization'] = $user
            ->getOrganization()
            ->getUniqueId();

        $userData['invite_token'] = '';
        $userData['email_address'] = $user->getEmailAddress();
        $userData['active'] = 1;

        $savedUser = $this
            ->factory
            ->getUserModify()
            ->update(
                $user->getId(),
                $userData
            );

        if ($savedUser) {
            // Redirect to logged in session.
            return $this->logUserIn();
        }

        $errors = $this
            ->factory
            ->getUserModify()
            ->getErrors();

        return $this->failWithErrors($user, $errors);
    }

    private function logUserIn()
    {
        /** @var Authenticate $auth */
        $auth = $this->factory->getContainer()->get('authentication');
        $authRoutes = $this->factory->getContainer()->get('defaultRoutes');

        $auth->validate();

        $redirect = $authRoutes[$auth->getUser()->getRole()];
        $auth->setAuthRedirectUrl($redirect);
        $roles = $this->factory->getContainer()->get('roles');
        $scope = $roles[$auth->getUser()->getRole()];

        return $auth->establishUserIsAuthenticated($scope);
    }

    private function failWithErrors($user, $errors)
    {
        return $this->render(
            $this->getRenderer()->fetch(
                'user/invite-form.phtml',
                [
                    'user' => $user,
                    'errors' => $errors
                ]
            ),
            'empty.phtml'
        );
    }

    private function redirectToLogin()
    {
        return $this
            ->response
            ->withRedirect(
                '/login',
                302
            );
    }
}

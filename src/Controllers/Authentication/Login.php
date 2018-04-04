<?php
namespace Controllers\Authentication;

use Psr\Container\ContainerInterface;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;
use Traits\RendererTrait;

class Login
{
    use RendererTrait;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $args;

    /**
     * @var Authenticate
     */
    private $auth;

    private $authRoutes;

    public function __construct(ContainerInterface $container)
    {
        //@TODO switch to service for all of this containers
        $this->container = $container;
        $this->renderer = $this->container->get('renderer');
        $this->auth = $this->container->get('authentication');
        $this->authRoutes = $this->container->get('defaultRoutes');
    }

    public function __invoke($request, $response, $args)
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        $this->auth->setResponse($this->response);
        $this->auth->setRequest($this->request);

        $post = $this->request->getParsedBody();

        if ($post) {
            return $this->processLogin();
        }

        return $this->login();
    }

    private function processLogin()
    {
        if (!$this->auth->validate()) {
            return $this->auth->unableToAuthenticate();
        }

        $redirect = $this->authRoutes[$this->auth->getUser()->getRole()];
        $this->auth->setAuthRedirectUrl($redirect);
        $roles = $this->container->get('roles');
        $scope = $roles[$this->auth->getUser()->getRole()];
        return $this->auth->establishUserIsAuthenticated($scope);
    }

    private function login()
    {
        if ($this->auth->isLogged()) {
            $redirect = $this->authRoutes[$this->auth->getUser()->getRole()];
            return $response = $this->response->withRedirect($redirect, 200);
        }

        $this->response = $this->response->withHeader('auth-redirect', true);
        return $this->render(
            $this->getRenderer()->fetch('login.phtml', []),
            'empty.phtml'
        );
    }
}

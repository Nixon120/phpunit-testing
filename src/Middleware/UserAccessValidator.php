<?php

namespace Middleware;

use AllDigitalRewards\UserAccessLevelEnum\UserAccessLevelEnum;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Authentication\Authenticate;
use Slim\Http\Response;

class UserAccessValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Authenticate
     */
    private $auth;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->auth = $this->container->get('authentication');
    }

    /**
     * @param ServerRequestInterface $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     */
    public function __invoke(
        ServerRequestInterface $request,
        Response $response,
        callable $next = null
    ) {
        if ($this->userHasPiiAccess() === false) {
            return $response->withJson(['message' => _('User does not have access to Participant Data')], 400);
        }

        return $next($request, $response);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function userHasPiiAccess(): bool
    {
        return !(new UserAccessLevelEnum())->isPiiLimited($this->auth->getUser()->getAccessLevel());
    }
}

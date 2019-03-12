<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;

class ParticipantStatusValidator
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Authenticate
     */
    private $auth;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
        $this->auth = $this->container->get('authentication');
    }

    /**
     * Kicks off token signing and authorization confirmation
     *
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
    
        $this->request = $request;
        $this->response = $response;

        if ($this->auth->getUser()->getActive() == false) {
            return $response->withJson([
                'message' => 'Participant not active'
            ], 403);
        }

        return $next($this->request, $this->response);
    }
}

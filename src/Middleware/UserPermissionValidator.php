<?php

namespace Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;

class UserPermissionValidator
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

    public function __construct(Authenticate $authenticate)
    {
        $this->auth = $authenticate;
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

        $this->auth->setRequest($this->request);
        $this->auth->setResponse($this->response);

        if ($this->auth->isAuthorized() === false) {
            /** @var \Slim\Http\Response $response */
            if ($this->auth->isApiRequest()
                || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
                )
            ) {
                return $response->withJson([
                    'message' => 'Forbidden'
                ], 401);
            }

            $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
            $body->write('Forbidden');
            $response = $response->withStatus(401);
            return $response = $response->withBody($body);
        }

        return $next($this->request, $this->response);
    }
}

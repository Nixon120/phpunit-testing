<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Participant\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use Traits\LoggerAwareTrait;

class LogApiRequestBodiesMiddleware
{
    use LoggerAwareTrait;
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ServiceFactory
     */
    private $participantService;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->participantService = $this->container->get('participant');
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
        $this->request = $request;
        $this->response = $response;
        $post = $this->request->getParsedBody()??[];
        $this->getLogger()->notice(
            'API POST Request Bodies',
            [
                'POST' => $post,
                'ROUTE' => $request->getAttribute('routeInfo')['request'][1] ?? null
            ]
        );

        return $next($this->request, $this->response);
    }
}

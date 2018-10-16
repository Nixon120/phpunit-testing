<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\CacheService;
use Slim\Http\Response;

class ProgramModifiedCacheClearMiddleware
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(
        ServerRequestInterface $request,
        Response $response,
        callable $next = null
    ){
        $this->request = $request;
        $this->response = $response;

        if (in_array($this->request->getMethod(), ['POST', 'PUT', 'DELETE'])) {

        }
        $response = $next($request, $response);


        return $response;
    }

    /**
     * @return CacheService
     */
    private function getCacheService()
    {
        return $this->container['cacheService'];
    }
}
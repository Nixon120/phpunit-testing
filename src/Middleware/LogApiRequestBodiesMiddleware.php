<?php

namespace Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;
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
     * @param ServerRequestInterface $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     */
    public function __invoke(
        ServerRequestInterface $request,
        Response $response,
        callable $next = null
    )
    {
        $this->request = $request;
        $this->response = $response;
        $post = $this->request->getParsedBody() ?? [];
        if (!empty($post)) {
            $this->getLogger()->notice(
                'API POST Request Bodies',
                [
                    'POST' => $post,
                    'ROUTE' => $request->getAttribute('routeInfo')['request'][1] ?? null
                ]
            );
        }
        return $next($this->request, $this->response);
    }
}

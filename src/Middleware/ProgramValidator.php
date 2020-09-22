<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Program\ServiceFactory;
use Slim\Http\Response;

class ProgramValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $args = $request->getAttribute('routeInfo')[2];
        $programUuid = $args['programUuid'] ?? null;
        if ($programUuid !== null) {
            /** @var ServiceFactory $programServiceFactory */
            $programServiceFactory = $this->container->get('program');
            $program = $programServiceFactory->getProgramRepository()->getProgram($programUuid);
            if (!empty($program)) {
                return $next($request, $response);
            }
        }

        return $response = $response->withStatus(404);
    }
}

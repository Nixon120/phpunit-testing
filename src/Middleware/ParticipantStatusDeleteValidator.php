<?php

namespace Middleware;

use AllDigitalRewards\StatusEnum\StatusEnum;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

class ParticipantStatusDeleteValidator
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
        $status = $request->getParsedBody()['status'] ?? '';
        if ((new StatusEnum())->hydrateStatus($status) === StatusEnum::DATADEL) {
            return $response->withJson(['message' => _('DATADEL can only be set by DELETING the participant.')], 400);
        }

        return $next($request, $response);
    }
}

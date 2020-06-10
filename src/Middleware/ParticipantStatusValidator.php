<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Participant\ServiceFactory;
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
        $args = ($request->getAttribute('route'))->getArguments();

        $participant = $this->participantService->getService()->getSingle($args['id']);

        if (is_null($participant) === true) {
            return $response->withJson(['message' => 'Resource does not exist'], 400);
        }

        if ($participant->isActive() == false) {
            return $response->withJson(['message' => 'Participant not active'], 400);
        }

        return $next($this->request, $this->response);
    }
}

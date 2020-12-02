<?php

namespace Middleware;

use AllDigitalRewards\StatusEnum\StatusEnum;
use Entities\Participant;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Participant\ServiceFactory;
use Slim\Http\Response;

class ParticipantStatusDeleteValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServiceFactory
     */
    private $participantService;

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
        $status = $request->getParsedBody()['status'] ?? '';
        if ($this->hasDeleteStatus($status)) {
            return $response->withJson(['message' => _('DATADEL can only be set by DELETING the participant.')], 400);
        }

        //Prevent any updates to a Participant IF DATADEL is their status
        if ($request->getMethod() === 'PUT') {
            $args = ($request->getAttribute('route'))->getArguments();
            $participant = $this->participantService->getService()->getSingle($args['id']);
            if (!$participant instanceof Participant) {
                return $response->withJson(['message' => _('Resource does not exist')], 400);
            }

            if ($this->hasDeleteStatus($participant->getStatus())) {
                return $response->withJson(
                    [
                        'message' => _('Participant status is DATADEL, no further updates can be made.')
                    ],
                    400
                );
            }
        }


        return $next($request, $response);
    }

    private function hasDeleteStatus($status)
    {
        return (new StatusEnum())->hydrateStatus($status) === StatusEnum::DATADEL;
    }
}

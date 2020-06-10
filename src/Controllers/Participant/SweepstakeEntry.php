<?php

namespace Controllers\Participant;

use Services\Participant\ServiceFactory;
use Services\Program\Exception\SweepstakeServiceException;
use Slim\Http\Request;
use Slim\Http\Response;

class SweepstakeEntry
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
    
        $this->request = $request;
        $this->response = $response;
        $this->factory = $factory;
    }

    public function create($organizationId, $uniqueId)
    {
        $participant = $this->factory->getParticipantRepository()
            ->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            if ($participant->isFrozen() === true || $participant->isActive() === false) {
                return $this->returnJson(400, ['Sweepstake entry not allowed. Participant is ' . $participant->getStatus()]);
            }
            $post = $this->request->getParsedBody() ?? [];
            try {
                $sweepstakeService = $this->factory->getSweepstakeService();
                $sweepstakeService->createSweepstakeEntry($participant, $post);
                return $this->returnJson(201, []);
            } catch (SweepstakeServiceException $e) {
                return $this->returnJson(400, [$e->getMessage()]);
            }
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

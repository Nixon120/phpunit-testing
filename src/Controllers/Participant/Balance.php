<?php

namespace Controllers\Participant;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;
use Slim\Http\Request;
use Slim\Http\Response;

class Balance
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
     * @var Participant
     */
    private $service;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->service = $factory->getBalanceService();
    }

    public function list($organizationId, $uniqueId)
    {
        $get = $this->request->getQueryParams();
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $get['participant_id'] = $participant->getId();
            $input = new InputNormalizer($get);
            $return = $this->service->get($input);
            $output = new OutputNormalizer($return);
            $response = $this->response->withStatus(200)
                ->withJson($output->getAdjustmentList($participant));
            return $response;
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function insert($organizationId, $uniqueId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];
            $input = new InputNormalizer($post);

            if ($adjustment = $this->service->createAdjustment($participant, $input)) {
                $output = new OutputNormalizer($adjustment);
                return $this->returnJson(201, $output->getAdjustment());
            } else {
                return $this->returnJson(400, $this->service->repository->getErrors());
            }
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function update($organizationId, $uniqueId, $adjustmentId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if (is_null($participant)) {
            return $this->returnJson(404, ['Participant Not Found']);
        }

        $adjustment = $this->service->getSingle($participant, $adjustmentId);

        if (is_null($adjustment)) {
            return $this->response->withJson(['Adjustment Not Found'], 404);
        }

        $post = $this->request->getParsedBody() ?? [];

        if (!empty($post['description'])) {
            $adjustment->setDescription($post['description']);
        }

        if (!empty($post['reference'])) {
            $adjustment->setReference($post['reference']);
        }

        if (!empty($post['completed_at'])) {
            $adjustment->setCompletedAt($post['completed_at']);
        }

        if ($this->service->updateAdjustment($adjustment)) {
            return $this->response->withStatus(202);
        }

        return $this->response->withJson(['Unable to save adjustment update.'], 500);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

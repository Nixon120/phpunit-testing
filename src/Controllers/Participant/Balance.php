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

    public function listToAndFromDates($organizationId, $uniqueId)
    {
        $get = $this->request->getQueryParams();
        $fromDate = $get['from_date'];
        $toDate = $get['to_date'];
        $reference = $get['reference'] ?? null;
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        $adjustments = $this->service->getParticipantCreditAdjustmentsByDate(
            $participant,
            $fromDate,
            $toDate,
            $reference
        );

        $response = $this->response->withStatus(200)
            ->withJson($adjustments);
        return $response;

    }

    public function single($organizationId, $uniqueId, $adjustmentId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $item = $this->service->getAdjustmentForWebhook($adjustmentId);
        $item->setParticipant($participant);

        if ($participant !== null && $item !== null) {
            $output = new OutputNormalizer($item);
            return $this->returnJson(200, $output->getAdjustment());
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

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

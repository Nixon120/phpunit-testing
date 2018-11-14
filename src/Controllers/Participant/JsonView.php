<?php
namespace Controllers\Participant;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Authentication\Authenticate;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;

class JsonView extends AbstractViewController
{
    /**
     * @var Participant
     */
    private $service;
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->service = $factory->getService();
        $this->factory = $factory;
    }

    public function list()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $return = $this->service->get($input);
        $output = new OutputNormalizer($return);
        $response = $this->response->withStatus(200)
            ->withJson($output->getList());
        return $response;
    }

    public function listByMeta()
    {
        $get = $this->request->getQueryParams();
        $key = key($get);
        $value = $get[$key];
        $participants = $this->service->repository->getParticipantsByMetaKeyValue(
            $key,
            $value
        );

        if (is_null($participants)) {
            return $this->renderJson404();
        }
        $response = $this->response->withStatus(200)
            ->withJson($participants);
        return $response;
    }

    public function single($id)
    {
        /** @var \Entities\Participant $participant */
        $participant = $this->service->repository->getParticipant($id);

        if (is_null($participant)) {
            return $this->renderJson404();
        }
        $output = new OutputNormalizer($participant);
        $response = $this->response->withStatus(200)
            ->withJson($output->get());
        return $response;
        //@TODO change shippping to varchar phinx
    }

    public function adjustmentList($participantId)
    {
        $participant = $this->service->getSingle($participantId);

        if (is_null($participant)) {
            return $this->renderGui404();
        }
        $adjustments = $this->factory->getBalanceService()->getParticipantAdjustments($participant);
        $output = new OutputNormalizer($adjustments);

        $response = $this->response->withStatus(200)
            ->withJson([
                'participant' => $participant,
                'adjustments' => $output->getAdjustmentList($participant)
            ]);
        return $response;
    }


    public function transaction($participantId, $transactionId)
    {
        $participant = $this->factory->getService()->getSingle($participantId);

        if (is_null($participant)) {
            return $this->renderGui404();
        }

        $transaction = $this->factory->getTransactionService()->getSingle($participant, $transactionId);

        if (is_null($transaction)) {
            return $this->renderGui404();
        }

        $output = new OutputNormalizer($transaction);
        $response = $this->response->withStatus(200)
            ->withJson([
                'participant' => $participant,
                'transaction' => $output->getTransaction()
            ]);
        return $response;
    }
}

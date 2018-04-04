<?php
namespace Controllers\Participant;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\Exception\TransactionServiceException;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;

class Transaction
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
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
        $this->service = $factory->getTransactionService();
    }

    public function addTransaction($organizationId, $uniqueId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];

            try {
                if ($transaction = $this->service->insert($organizationId, $uniqueId, $post)) {
                    //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
                    $output = new OutputNormalizer($transaction);
                    return $this->returnJson(201, $output->getTransaction());
                } else {
                    return $this->returnJson(400, $this->service->repository->getErrors());
                }
            } catch (TransactionServiceException $e) {
                return $this->returnJson(400, $e->getMessage());
            }
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function transactionList($organizationId, $uniqueId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            $transactions = $this->service->get($participant);
            $output = new OutputNormalizer($transactions);
            return $this->returnJson(200, $output->getTransactionList());
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function single($organizationId, $uniqueId, $transactionId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            $transaction = $this->service->getSingle($participant, $transactionId);
            $output = new OutputNormalizer($transaction);
            return $this->returnJson(200, $output->getTransaction());
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function singleItem($organizationId, $uniqueId, $guid)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $item = $this->service->getSingleItem($guid);

        if ($participant !== null && $item !== null) {
            $output = new OutputNormalizer($item);
            return $this->returnJson(200, $output->getItem());
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

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
                return $this->returnJson(400, [$e->getMessage()]);
            }
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function customerServiceTransaction($organizationId, $uniqueId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];
            $post['issue_points'] = false;
            $offlineRedemptions = $this->service->participantRepository->getOfflineRedemptions($participant->getProgram());
            $selectedProduct = $post['products'][0]['sku'];

            if (in_array($selectedProduct, $offlineRedemptions) === false) {
                return $this->returnJson(404, ['Product does not match allowable offline redemption products for this program.']);
            }

            try {
                if ($transaction = $this->service->insert($organizationId, $uniqueId, $post)) {
                    //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
                    $output = new OutputNormalizer($transaction);
                    return $this->returnJson(201, $output->getTransaction());
                } else {
                    return $this->returnJson(400, $this->service->repository->getErrors());
                }
            } catch (TransactionServiceException $e) {
                return $this->returnJson(400, [$e->getMessage()]);
            }
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function transactionList($organizationId, $uniqueId)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $transactionUniqueIds = $this->request->getQueryParam('unique_id');

        if ($participant !== null) {
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            $transactions = $this->service->get($participant, $transactionUniqueIds);
            //The unique id passed in was bad
            if (empty($transactions) === true && $transactionUniqueIds !== null) {
                return $this->returnJson(404, ['Unique Ids Not Found']);
            }
            if (empty($transactions) === true) {
                return $this->returnJson(200, []);
            }
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

    public function updateMeta($organizationId, $uniqueId, $guid)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $transaction = $this->service->getSingleItem($guid);
        $data = $this->request->getParsedBody() ?? [];
        $meta = $data['meta'] ?? null;

        if ($participant === null && $transaction === null) {
            return $this->returnJson(400, ['Resource does not exist']);
        }

        $this->service->updateSingleItemMeta($transaction['transaction_id'], $meta);

        return $this->returnJson(201, ['Transaction Meta updated']);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

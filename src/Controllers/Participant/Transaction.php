<?php

namespace Controllers\Participant;

use Entities\TransactionMeta;
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

    public function addTransaction($organizationId, $uniqueId, $userAccessLevel)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];

            try {
                if ($transaction = $this->service->insert($organizationId, $uniqueId, $post)) {
                    //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
                    $output = new OutputNormalizer($transaction);
                    $output->setUserAccessLevel($userAccessLevel);
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

    public function customerServiceTransaction($organizationId, $uniqueId, $userAccessLevel)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];
            $post['issue_points'] = false;
            $offlineRedemptions = $this->service->participantRepository->getOfflineRedemptions(
                $participant->getProgram()
            );
            $selectedProduct = $post['products'][0]['sku'];

            if (in_array($selectedProduct, $offlineRedemptions) === false) {
                return $this->returnJson(
                    404,
                    ['Product does not match allowable offline redemption products for this program.']
                );
            }

            try {
                if ($transaction = $this->service->insert($organizationId, $uniqueId, $post)) {
                    //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
                    $output = new OutputNormalizer($transaction);
                    $output->setUserAccessLevel($userAccessLevel);
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

    public function transactionList($organizationId, $uniqueId, $userAccessLevel, $year = null)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $transactionUniqueIds = $this->request->getQueryParam('unique_id');

        if ($participant !== null) {
            if ($year !== null && ($year > 2015 && $year <= date('yy')) === false) {
                return $this->returnJson(400, [$year . ' is not a valid year']);
            }
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            $transactions = $this->service->get($participant, $transactionUniqueIds, $year);
            //The unique id passed in was bad
            if (empty($transactions) === true && $transactionUniqueIds !== null) {
                return $this->returnJson(404, ['Unique Ids Not Found']);
            }
            if (empty($transactions) === true) {
                return $this->returnJson(200, []);
            }
            $output = new OutputNormalizer($transactions);
            $output->setUserAccessLevel($userAccessLevel);
            return $this->returnJson(200, $output->getTransactionList());
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function single($organizationId, $uniqueId, $transactionId, $userAccessLevel)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            $transaction = $this->service->getSingle($participant, $transactionId);
            $output = new OutputNormalizer($transaction);
            $output->setUserAccessLevel($userAccessLevel);
            return $this->returnJson(200, $output->getTransaction());
        }
        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function singleItem($organizationId, $uniqueId, $guid, $userAccessLevel)
    {
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $item = $this->service->getSingleItem($guid);

        if ($participant !== null && $item !== null) {
            $output = new OutputNormalizer($item);
            $output->setUserAccessLevel($userAccessLevel);
            return $this->returnJson(200, $output->getItem());
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    public function addReissueDate($organizationId, $uniqueId, $guid)
    {
        $transaction_item = $this->service->getSingleItem($guid);
        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        $reissueDate = $this->request->getParsedBody() ?? null;

        if (empty($reissueDate['reissue_date']) === true
            || $participant === null
            || $transaction_item === null
        ) {
            return $this->returnJson(400, ['Resource does not exist']);
        }

        $updated = $this->service->setReissueDate($guid, $reissueDate['reissue_date']);
        if ($updated === true) {
            return $this->response->withStatus(202);
        }

        return $this->returnJson(500, ['Internal Server Error']);
    }

    public function patchMeta($organizationId, $uniqueId, $transactionId)
    {
        if (!is_numeric($transactionId)) {
            // Transaction Item GUID provided (rather than Transaction ID)
            $transaction_item = $this->service->getSingleItem($transactionId);
            if ($transaction_item === null) {
                return $this->returnJson(400, ['Resource does not exist']);
            }
            $transactionId = $transaction_item['transaction_id'];
        }

        $participant = $this->service->participantRepository->getParticipantByOrganization($organizationId, $uniqueId);
        if ($participant === null) {
            return $this->returnJson(400, ['Resource does not exist']);
        }

        $transaction = $this->service->getSingle($participant, $transactionId);
        if ($transaction === null) {
            return $this->returnJson(400, ['Resource does not exist']);
        }

        $meta = $this->request->getParsedBody() ?? [];

        if (empty($meta)) {
            return $this->returnJson(400, [
                'meta' => [
                    'Meta::ILLEGAL_META' => _("Transaction Meta is not valid, please provide valid key:value non-empty pairs.")
                ]
            ]);
        }

        //is TransactionMeta well-formed
        if ($this->service->hasValidMeta($meta) === false) {
            return $this->returnJson(400, $this->service->repository->getErrors());
        }

        $this->service->updateSingleItemMeta($transactionId, $meta);

        return $this->response->withStatus(202);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

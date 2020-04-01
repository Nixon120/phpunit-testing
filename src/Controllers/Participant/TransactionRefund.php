<?php

namespace Controllers\Participant;

use Entities\User;
use Services\Authentication\Authenticate;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class TransactionRefund
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
     * @var Container
     */
    private $container;

    /**
     * @var \Services\Participant\Transaction
     */
    private $transactionService;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function getAuthUser():?User
    {
        /** @var Authenticate $auth */
        $auth = $this->getContainer()->get('authentication');
        return $auth->getUser();
    }

    /**
     * @return mixed
     */
    public function getTransactionService(): \Services\Participant\Transaction
    {
        if ($this->transactionService === null) {
            $this->transactionService = $this->getContainer()->get('participant')->getTransactionService();
        }

        return $this->transactionService;
    }

    /**
     * @param mixed $transactionService
     */
    public function setTransactionService($transactionService): void
    {
        $this->transactionService = $transactionService;
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        $this->request = $request;
        $this->response = $response;

        $guid = $args['item_guid'] ?? null;

        $item = $this->getTransactionService()->getSingleItem($guid);
        if ($item === null) {
            return $this->response = $this->response->withJson(404, ['Resource does not exist']);
        }

        if ($this->request->isPost()) {
            return $this->issueRefundRequest($item);
        }

        return $this->getRefundRequest($guid);
    }

    private function getRefundRequest($guid)
    {
        $refund = $this->getTransactionService()->getRefundByGuid($guid);

        if ($refund === null) {
            return $this->response = $this->response->withJson(['Resource does not exist'], 404);
        }

        $aUser = $refund->getUser()->toArray();
        $aRefund = $refund->toArray();

        unset($aUser['password'], $aUser['invite_token'], $aUser['organization_id']);
        $aRefund['item'] = $refund->getItem();
        $aRefund['user'] = $aUser;
        unset($aRefund['transaction_id'], $aRefund['transaction_item_id']);
        return $this->response = $this->response->withJson($aRefund, 200);
    }

    private function issueRefundRequest(array $item)
    {
        $notes = $this->request->getParsedBody()['notes'] ?? null;

        try {
            $success = $this->getTransactionService()->initiateRefund($this->getAuthUser(), $item, $notes);

            if ($success === true) {
                return $this->response = $this->response->withStatus(201);
            }
            $message = 'There was a problem with your request';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return $this->response = $this->response->withJson([$message], 400);
    }
}

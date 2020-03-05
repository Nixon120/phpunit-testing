<?php

namespace Controllers\Participant;

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

    /**
     * @return mixed
     */
    public function getTransactionService(): \Services\Participant\Transaction
    {
        if($this->transactionService === null) {
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

        $transactionId = $args['transaction_id'] ?? null;
        $transactionItemGuid = $args['item_guid'] ?? null;

        $item = $this->getTransactionService()->getTransactionItemByTransactionIdAndGuid($transactionId, $transactionItemGuid);

        if ($item === null) {
            return $this->returnJson(404, ['Resource does not exist']);
        }

        $transactionItemId = $item['id'];
        $notes = $this->request->getParsedBody()['notes'] ?? null;

        try {
            $success = $this->getTransactionService()->initiateRefund($transactionId, $transactionItemId, $notes);

            if ($success === true) {
                return $this->returnJson(201);
            }
            $message = 'There was a problem with your request';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return $this->returnJson(400, [
            'errors' => [
                $message
            ]
        ]);
    }

    private function returnJson($statusCode, ?array $return = null)
    {
        $this->response = $this->response->withStatus($statusCode);
        if($return !== null && !empty($return)) {
            $this->response = $this->response->withJson($return);
        }

        return $this->response;
    }
}

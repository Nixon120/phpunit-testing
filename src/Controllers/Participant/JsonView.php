<?php
namespace Controllers\Participant;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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

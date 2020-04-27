<?php

namespace Controllers\Participant;

use Entities\User;
use Services\Authentication\Authenticate;
use Services\Participant\Participant;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class TransactionReturn
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

    /**
     * @var Participant
     */
    private $participantService;

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

    /**
     * @return Participant
     */
    public function getParticipantService(): Participant
    {
        if($this->participantService === null) {
            $this->participantService = $this->getContainer()->get('participant')->getService();
        }
        return $this->participantService;
    }

    /**
     * @param Participant $participantService
     */
    public function setParticipantService(Participant $participantService): void
    {
        $this->participantService = $participantService;
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
            return $this->issueReturnRequest($item);
        }

        return $this->getReturnRequest($guid);
    }

    private function getReturnRequest($guid)
    {
        $return = $this->getTransactionService()->getReturnByGuid($guid);

        if ($return === null) {
            return $this->response = $this->response->withJson(['Resource does not exist'], 404);
        }

        $transaction = $this->transactionService->getTransactionRepository()->getTransaction($return->getTransactionId());
        $aUser = $return->getUser()->toArray();
        $aReturn = $return->toArray();
        $item = $return->getItem();
        $participant = $this->scrubParticipant($this->getParticipantService()->getById($transaction->getParticipantId()));
        unset($item['id']);
        unset($aUser['password'], $aUser['role'], $aUser['id'], $aUser['invite_token'], $aUser['organization_id']);
        unset($aReturn['user_id'], $aReturn['transaction_id'], $aReturn['transaction_item_id']);

        $aReturn['item'] = $item;
        $aReturn['user'] = $aUser;
        $aReturn['participant'] = $participant;
        return $this->response = $this->response->withJson($aReturn, 200);
    }

    private function scrubParticipant(\Entities\Participant $participant)
    {
        $program = $participant->getProgram()->getUniqueId();
        $organization = $participant->getOrganization()->getUniqueId();
        $address = $participant->getAddress();
        if(!empty($address)) {
            $address = $address->toArray();
            unset($address['participant_id'], $address['id'], $address['reference_id']);
        }
        $meta = $participant->getMeta();
        $participant = $participant->toArray();
        $participant['program'] = $program;
        $participant['organization'] = $organization;
        $participant['address'] = $address;
        $participant['meta'] = $meta;
        unset($participant['program_id'], $participant['organization_id'], $participant['sso'], $participant['password'], $participant['id'], $participant['address_reference']);
        return $participant;
    }

    private function issueReturnRequest(array $item)
    {
        $notes = $this->request->getParsedBody()['notes'] ?? null;

        try {
            $success = $this->getTransactionService()->initiateReturn($this->getAuthUser(), $item, $notes);

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

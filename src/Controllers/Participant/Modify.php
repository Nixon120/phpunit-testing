<?php
namespace Controllers\Participant;

use Controllers\AbstractModifyController;
use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;

class Modify extends AbstractModifyController
{
    /**
     * @var Participant
     */
    private $service;
    /**
     * @var \Services\Participant\Balance
     */
    private $balanceService;

    public function __construct(
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->service = $factory->getService();
        $this->balanceService = $factory->getBalanceService();
    }

    public function insert(string $agentEmailAddress)
    {
        $post = $this->request->getParsedBody()??[];
        unset($post['credit']);
        if ($participant = $this->service->insert($post, $agentEmailAddress)) {
            $this->service->setParticipantCreditsToZeroIfCancelled(
                $this->balanceService,
                $participant
            );
            $output = new OutputNormalizer($participant);
            return $this->returnJson(201, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    public function update($id, string $agentEmailAddress)
    {
        $post = $this->request->getParsedBody()??[];
        unset($post['credit']);
        //@TODO: perhaps we can figure out a clean way to remove unneeded fields without explicitly removing them
        //We don't need this.
        if ($participant = $this->service->update($id, $post, $agentEmailAddress)) {
            $this->service->setParticipantCreditsToZeroIfCancelled(
                $this->balanceService,
                $participant
            );
            $output = new OutputNormalizer($participant);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    /**
     * @param $id
     * @param string $agentEmailAddress
     * @return Response
     */
    public function remove($id, string $agentEmailAddress)
    {
        /** @var \Entities\Participant $participant */
        $participant = $this->service->repository->getParticipant($id);

        if (is_null($participant)) {
            return $this->returnJson(404, ['Participant not valid']);
        }

        if ($this->service->remove($participant, $agentEmailAddress) === true) {
            return $this->response->withStatus(204);
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }
}

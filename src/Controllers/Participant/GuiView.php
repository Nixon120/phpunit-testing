<?php
namespace Controllers\Participant;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;
use Slim\Views\PhpRenderer;

class GuiView extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderCreatePage()
    {
        return $this->render(
            $this->getRenderer()->fetch('participant/form.phtml', [
                'participant' => new \Entities\Participant,
                'formAction' => '/participant/create',
                'formContext' => 'create'
            ])
        );
    }

    public function renderList()
    {
        return $this->render(
            $this->getRenderer()->fetch('participant/list.phtml')
        );
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();

        $input = new InputNormalizer($get);
        $participants = $this->factory->getService()->get($input);

        if (isset($get['method']) && $get['method'] === 'json') {
            $response = $this->response->withStatus(200)
                ->withJson($participants);

            return $response;
        }

        return $this->render(
            $this->getRenderer()->fetch('participant/loop.phtml', [
                'participants' => $participants
            ]),
            'empty.phtml'
        );
    }

    public function renderSingle($id)
    {
        $participant = $this->factory->getService()->getSingle($id);

        if (is_null($participant)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch('participant/form.phtml', [
                'participant' => $participant,
                'formAction' => '/participant/' . $participant->getUniqueId() . '/view',
                'formContext' => 'update'
            ])
        );
    }

    public function renderAdjustmentList($participantId)
    {
        $participant = $this->factory->getService()->getSingle($participantId);

        if (is_null($participant)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch('participant/adjustments.phtml', [
                'participant' => $participant,
                'adjustments' => $this->factory->getBalanceService()->getParticipantAdjustments($participant)
            ])
        );
    }

    public function renderTransaction($participantId, $transactionId)
    {
        $participant = $this->factory->getService()->getSingle($participantId);

        if (is_null($participant)) {
            return $this->renderGui404();
        }

        $transaction = $this->factory->getTransactionService()->getSingle($participant, $transactionId);

        if (is_null($transaction)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch('participant/transaction.phtml', [
                'participant' => $participant,
                'transaction' => $transaction
            ])
        );
    }
}

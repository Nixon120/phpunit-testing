<?php

namespace Events\Listeners\Transaction;

use AllDigitalRewards\AMQP\MessagePublisher;
use Entities\Event;
use Entities\TransactionItemRefund;
use Entities\Webhook;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Repositories\WebhookRepository;
use Services\Participant\Participant as ParticipantService;
use Services\Participant\Participant;
use Services\Participant\ServiceFactory;
use Services\Participant\Transaction;
use Services\Webhook\WebhookPublisherService;

class TransactionItemRefundWebhookListener extends AbstractListener
{
    /**
     * @var WebhookRepository
     */
    private $webhookRepository;
    /**
     * @var Event
     */
    private $event;
    /**
     * @var Transaction
     */
    private $transactionService;
    /**
     * @var ParticipantService
     */
    private $participantService;

    public function __construct(
        MessagePublisher $publisher,
        ServiceFactory $serviceFactory,
        WebhookRepository $webhookRepository
    )
    {
        parent::__construct($publisher);
        $this->webhookRepository = $webhookRepository;
        $this->transactionService = $serviceFactory->getTransactionService();
        $this->participantService = $serviceFactory->getService();
    }

    /**
     * @param EventInterface|Event $event
     * @return bool
     */
    public function handle(EventInterface $event)
    {
        $this->event = $event;

        try {
            $refund = $this->getTransactionItemRefund();
            $transaction = $this->transactionService->getTransactionRepository()->getTransaction($refund->getTransactionId());
            $participant = $this->getParticipant($transaction->getParticipantId());
            $organization = $participant->getOrganization();

            $webhooks = $this
                ->webhookRepository
                ->getOrganizationAndParentWebhooks(
                    $organization,
                    'TransactionItemRefundWebhook.create'
                );

            // Iterate thru the webhooks & execute.
            foreach ($webhooks as $webhook) {
                $this->executeWebhook($webhook, $refund);
            }
        } catch(\Exception $e) {
            exit(1);
        }

        return true;
    }

    private function executeWebhook(
        Webhook $webhook,
        TransactionItemRefund $refund
    )
    {
        $data = $refund->toArray();
        unset($data['id']);
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $data);
    }

    /**
     * @return \Entities\TransactionItemRefund|null
     */
    private function getTransactionItemRefund()
    {
        return $this->transactionService->getRefundById($this->event->getEntityId());
    }

    /**
     * @param $id
     * @return \Entities\Participant|null
     */
    private function getParticipant($id)
    {
        return $this->participantService->getById($id);
    }
}

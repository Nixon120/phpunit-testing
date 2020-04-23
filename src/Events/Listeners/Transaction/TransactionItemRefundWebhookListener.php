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

            $item = $refund->getItem();
            $user = $refund->getUser()->toArray();
            $refund = $refund->toArray();

            unset($user['organization'], $user['password'], $user['role'], $user['invite_token'], $user['organization_id'], $user['id']);
            unset($refund['user_id'], $refund['transaction_item_id'], $refund['transaction_id']);
            $refund['item'] = $item;
            $refund['user'] = $user;
            $refund['participant'] = $this->scrubParticipant($participant);
            // Iterate thru the webhooks & execute.
            foreach ($webhooks as $webhook) {
                $this->executeWebhook($webhook, $refund);
            }
        } catch(\Exception $e) {
            exit(1);
        }

        return true;
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

    private function executeWebhook(
        Webhook $webhook,
        array $refund
    )
    {
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $refund);
    }

    /**
     * @return TransactionItemRefund|null
     * @throws \Exception
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

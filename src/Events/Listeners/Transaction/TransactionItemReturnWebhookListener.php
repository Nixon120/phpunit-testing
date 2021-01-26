<?php

namespace Events\Listeners\Transaction;

use AllDigitalRewards\AMQP\MessagePublisher;
use Entities\Event;
use Entities\TransactionItemReturn;
use Entities\Webhook;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Repositories\WebhookRepository;
use Services\Participant\Participant as ParticipantService;
use Services\Participant\Participant;
use Services\Participant\ServiceFactory;
use Services\Participant\Transaction;
use Services\Webhook\WebhookPublisherService;

class TransactionItemReturnWebhookListener extends AbstractListener
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
            $return = $this->getTransactionItemReturn();
            $transaction = $this->transactionService->getTransactionRepository()->getTransaction(
                $return->getTransactionId()
            );
            $participant = $this->getParticipant($transaction->getParticipantId());
            $organization = $participant->getOrganization();

            $webhooks = $this
                ->webhookRepository
                ->getOrganizationAndParentWebhooks(
                    $organization,
                    'TransactionItemReturnWebhook.create'
                );

            $item = $return->getItem();
            $user = $return->getUser()->toArray();
            $return = $return->toArray();

            unset($user['organization'], $user['password'], $user['role'], $user['invite_token'], $user['organization_id'], $user['id']);
            unset($return['user_id'], $return['transaction_item_id'], $return['transaction_id']);
            $return['item'] = $item;
            $return['user'] = $user;
            $return['participant'] = $this->scrubParticipant($participant);
            // Iterate thru the webhooks & execute.
            foreach ($webhooks as $webhook) {
                $this->executeWebhook($webhook, $return);
            }
        } catch (\Exception $e) {
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
        array $return
    )
    {
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $return);
    }

    /**
     * @return TransactionItemReturn|null
     * @throws \Exception
     */
    private function getTransactionItemReturn()
    {
        return $this->transactionService->getReturnById($this->event->getEntityId());
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

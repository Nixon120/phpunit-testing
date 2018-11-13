<?php

namespace Events\Listeners\Transaction;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Participant\OutputNormalizer;
use Entities\Event;
use Entities\Webhook;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Repositories\WebhookRepository;
use Services\Participant\Participant as ParticipantService;
use Services\Participant\Participant;
use Services\Webhook\WebhookPublisherService;

class AdjustmentWebhookListener extends AbstractListener
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
     * @var ParticipantService
     */
    private $participantService;

    public function __construct(
        MessagePublisher $publisher,
        ParticipantService $participantService,
        WebhookRepository $webhookRepository
    ) {
        parent::__construct($publisher);
        $this->webhookRepository = $webhookRepository;
        $this->participantService = $participantService;
    }

    /**
     * @param EventInterface|Event $event
     * @return bool
     */
    public function handle(EventInterface $event)
    {
        $this->event = $event;

        $participant = $this->fetchParticipant();

        // Determine the Organization the event belongs to.
        $organization = $participant->getOrganization();

        if ($event->getName() == 'Adjustment.create') {
            // Get all configured Adjustment.create Webhooks for Org & Parent Orgs.
            $webhooks = $this
                ->webhookRepository
                ->getOrganizationAndParentWebhooks(
                    $organization,
                    'Adjustment.create'
                );

            // Iterate thru the webhooks & execute.
            foreach ($webhooks as $webhook) {
                $this->executeWebhook($webhook, $transaction);
            }
        }

        if (strstr($event->getName(), 'Adjustment.create.webhook.') !== false) {
            // Get Single Webhook to Execute again.
            // This will only occur if it initially failed for some reason.
            // Adjustment.create.webhook.{webhookId}
            $webhookId = substr(
                $event->getName(),
                strrpos($event->getName(), '.')
            );

            $webhook = $this->webhookRepository->getWebhook($webhookId);
            if (!$webhook instanceof Webhook) {
                // Webhook not found.
                // This is bad, we should probably catch an exception here.
            }

            $this->executeWebhook($webhook, $participant);
        }

        return true;
    }

    private function executeWebhook(
        Webhook $webhook,
        \Entities\Participant $participant
    ) {
        $outputNormalizer = new OutputNormalizer($participant);
        $data = $outputNormalizer->getAdjustment();
        
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $data);
    }

    /**
     * @return \Entities\Participant
     */
    private function fetchParticipant()
    {
        return $this
            ->participantService
            ->getById($this->event->getEntityId());
    }
}

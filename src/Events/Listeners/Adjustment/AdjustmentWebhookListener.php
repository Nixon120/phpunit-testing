<?php

namespace Events\Listeners\Adjustment;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Participant\OutputNormalizer;
use Entities\Adjustment;
use Entities\Event;
use Entities\Webhook;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Repositories\WebhookRepository;
use Services\Participant\Balance;
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
    /**
     * @var Balance
     */
    private $balanceService;

    public function __construct(
        MessagePublisher $publisher,
        ParticipantService $participantService,
        Balance $balance,
        WebhookRepository $webhookRepository
    ) {
        parent::__construct($publisher);
        $this->webhookRepository = $webhookRepository;
        $this->participantService = $participantService;
        $this->balanceService = $balance;
    }

    /**
     * @param EventInterface|Event $event
     * @return bool
     */
    public function handle(EventInterface $event)
    {
        $this->event = $event;

        $adjustment = $this->getAdjustment();
        $participant = $this->getParticipant($adjustment->getParticipantId());
        $adjustment->setParticipant($participant);
        $organization = $participant->getOrganization();

        $types = ['debit', 'credit'];
        foreach ($types as $type) {
            if ($event->getName() == 'Adjustment.' . $type) {
                $webhooks = $this
                    ->webhookRepository
                    ->getOrganizationAndParentWebhooks(
                        $organization,
                        'Adjustment.' . $type
                    );

                // Iterate thru the webhooks & execute.
                foreach ($webhooks as $webhook) {
                    $this->executeWebhook($webhook, $adjustment);
                }
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

            $this->executeWebhook($webhook, $adjustment);
        }

        return true;
    }

    private function executeWebhook(
        Webhook $webhook,
        Adjustment $adjustment
    ) {
        $outputNormalizer = new OutputNormalizer($adjustment);
        $data = $outputNormalizer->getAdjustment();
        
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $data);
    }

    /**
     * @return Adjustment|null
     */
    private function getAdjustment()
    {
        return $this->balanceService->getAdjustmentForWebhook($this->event->getEntityId());
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

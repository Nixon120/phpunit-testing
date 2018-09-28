<?php

namespace Events\Listeners\Transaction;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\Services\Catalog\Entity\InventoryApproveRequest;
use Controllers\Participant\OutputNormalizer;
use Entities\Event;
use Entities\Organization;
use Entities\Transaction;
use Entities\Webhook;
use Events\Listeners\AbstractListener;
use Firebase\JWT\JWT;
use League\Event\EventInterface;
use Repositories\WebhookRepository;
use Services\Participant\Transaction as TransactionService;
use Services\Participant\Participant as ParticipantService;
use Services\Webhook\WebhookPublisherService;

class TransactionWebhookListener extends AbstractListener
{
    /**
     * @var Transaction
     */
    private $transactionService;
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
        TransactionService $transactionService,
        ParticipantService $participantService,
        WebhookRepository $webhookRepository
    ) {
        parent::__construct($publisher);
        $this->transactionService = $transactionService;
        $this->webhookRepository = $webhookRepository;
        $this->participantService = $participantService;
    }

    private function generateSystemAuthToken()
    {
        $now = new \DateTime();
        $future = new \DateTime("now +2 hours");
        $jti = base64_encode(random_bytes(16));
        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "sub" => 'superadmin@alldigitalrewards.com',
            "user" => [
                'id' => 'An-ID',
                'firstname' => 'Super',
                'lastname' => 'Admin'
            ],
            "scope" => ['product.all']
        ];

        $secret = getenv("JWT_SECRET");
        $token = JWT::encode($payload, $secret, "HS256");
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
        return $data['token'];
    }

    private function approveInventoryHold(Transaction $transaction): bool
    {
        $authToken = $this->generateSystemAuthToken();
        $catalog = $this->transactionService->getTransactionRepository()
            ->getCatalog();

        $catalog->setToken($authToken);

        foreach($transaction->getItems() as $item) {
            if($catalog->getInventoryHold($item->getGuid()) === true) {

                $inventoryHoldApprove = new InventoryApproveRequest([
                    'guid' => $item->getGuid()
                ]);

                $success = $catalog->setInventoryApproved($inventoryHoldApprove);
                if($success === false) {
                    // Any failures will just requeue the event, approving inventory twice
                    // won't hurt anything
                    return false;
                }
            }

        }

        return true;
    }

    /**
     * @param EventInterface|Event $event
     * @return bool
     */
    public function handle(EventInterface $event)
    {
        $this->event = $event;

        $transaction = $this->fetchTransaction();
        $inventoryHoldsApproved = $this->approveInventoryHold($transaction);

        if($inventoryHoldsApproved === false) {
            $event->setName('Transaction.create');
            $this->reQueueEvent($event);
            return false;
        }

        // Determine the Organization the event belongs to.
        $organization = $this->fetchOrganization($transaction);

        if ($event->getName() == 'Transaction.create') {
            // Get all configured Transaction.create Webhooks for Org & Parent Orgs.
            $webhooks = $this
                ->webhookRepository
                ->getOrganizationAndParentWebhooks(
                    $organization,
                    'Transaction.create'
                );

            // Iterate thru the webhooks & execute.
            foreach ($webhooks as $webhook) {
                $this->executeWebhook($webhook, $transaction);
            }
        }

        if (strstr($event->getName(), 'Transaction.create.webhook.') !== false) {
            // Get Single Webhook to Execute again.
            // This will only occur if it initially failed for some reason.
            // Transaction.create.webhook.{webhookId}
            $webhookId = substr(
                $event->getName(),
                strrpos($event->getName(), '.')
            );

            $webhook = $this->webhookRepository->getWebhook($webhookId);
            if (!$webhook instanceof Webhook) {
                // Webhook not found.
                // This is bad, we should probably catch an exception here.
            }

            $this->executeWebhook($webhook, $transaction);
        }

        return true;
    }

    private function executeWebhook(
        Webhook $webhook,
        Transaction $transaction
    ) {
        // Use Webhook Transmittal service to POST the transactions.
        $outputNormalizer = new OutputNormalizer($transaction);
        $data = $outputNormalizer->getTransaction();
        
        // This is where we use a Webhook publishing service.
        $webhookPublisher = new WebhookPublisherService();
        $webhookPublisher->publish($webhook, $data);
    }

    /**
     * @return Transaction
     */
    private function fetchTransaction()
    {
        // Get the transaction.
        $transaction = $this
            ->getTransactionRepo()
            ->getTransaction(
                $this->event->getEntityId()
            );

        $participant = $this
            ->participantService
            ->getById($transaction->getParticipantId());

        // Return a fully hydrated Transaction
        return $this
            ->getTransactionRepo()
            ->getParticipantTransaction($participant, $transaction->getId());
    }

    /**
     * @param Transaction $transaction
     * @return Organization
     */
    private function fetchOrganization(Transaction $transaction)
    {
        return $this
            ->getTransactionRepo()
            ->getTransactionOrganization($transaction);
    }

    /**
     * @return \Repositories\TransactionRepository
     */
    private function getTransactionRepo()
    {
        return $this
            ->transactionService
            ->getTransactionRepository();
    }
}

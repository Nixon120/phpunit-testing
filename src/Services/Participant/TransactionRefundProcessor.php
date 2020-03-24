<?php

namespace Services\Participant;

use Entities\Event;
use Events\EventPublisherFactory;
use Slim\Container;

class TransactionRefundProcessor
{
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
     * @return mixed
     */
    public function getTransactionService(): \Services\Participant\Transaction
    {
        if($this->transactionService === null) {
            $this->transactionService = $this->getContainer()->get('participant')->getTransactionService();
        }

        return $this->transactionService;
    }

    public function getDatabase(): \PDO
    {
        return $this->getTransactionService()->getTransactionRepository()->getDatabase();
    }

    public function processPendingRefundRequests(): bool
    {
        $pendingCollection = $this->getPendingRefunds();
        foreach($pendingCollection as $refund) {
            if($this->issueRefund($refund) === false) {
                // Report incomplete refund issuance and continue
                // Refund failed, so what now?
                exit(1);
            }

            $this->setRefundAsProcessed($refund['id']);
            $this->publishRefundWebhookEvent($refund['id']);
        }

        return true;
    }

    private function issueRefund(array $refund): bool
    {
        $success = $this->issueCreditAdjustment($refund);
        // send data to RA queue for dispatch to RA refund endpoint
        return $success;
    }

    private function setRefundAsProcessed(int $refundId)
    {
        $this->getDatabase()->query(<<<SQL
UPDATE transaction_item_refund SET complete = 1 WHERE id = {$refundId} 
SQL
        );
    }

    private function publishRefundWebhookEvent($refundId)
    {
        $event = new Event();
        $event->setName('TransactionItemRefundWebhook.create');
        $event->setEntityId($refundId);
        $this->getEventPublisher()->publish(json_encode($event));
    }

    private function getEventPublisher()
    {
        $eventPublisherFactory = new EventPublisherFactory($this->container);
        return $eventPublisherFactory();
    }

    private function issueCreditAdjustment(array $refund): bool
    {
        $participant = $this->getParticipantService()->getSingle($refund['participant_unique_id']);
        $description = 'Refund';
        if(!empty(trim($refund['notes']))) {
            $description .= ': ' . $refund['notes'];
        }

        return $this->getTransactionService()->adjustPoints(
            $participant,
            'credit',
            $refund['total_refund_amount'],
            $refund['transaction_id'],
            $description,
            $refund['guid']
        ) !== null;
    }

    private function getPendingRefunds(): array
    {
        $sql = <<<SQL
SELECT 
    (SELECT program.point FROM program WHERE program.id = participant.program_id) as program_point_value,
    participant.unique_id as participant_unique_id, 
    ((transactionproduct.retail + transactionproduct.handling + transactionproduct.shipping) * transactionitem.quantity) as total_refund_amount,
    transactionitem.transaction_id,
    transactionitem.guid,
    transaction_item_refund.* 
FROM transaction_item_refund 
JOIN transactionitem ON transaction_item_refund.transaction_item_id = transactionitem.id
JOIN transactionproduct ON transactionitem.reference_id = transactionproduct.reference_id
JOIN `transaction` ON transaction_item_refund.transaction_id = `transaction`.id
JOIN participant ON `transaction`.participant_id = participant.id
WHERE complete = 0
SQL;

        $sth = $this->getDatabase()->query($sql);
        return $sth->fetchAll();
    }

}

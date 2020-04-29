<?php

namespace Services\Participant;

use Entities\Event;
use Events\EventPublisherFactory;
use Slim\Container;

class TransactionReturnProcessor
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

    public function processPendingReturnRequests(): bool
    {
        $pendingCollection = $this->getPendingReturns();
        foreach($pendingCollection as $return) {
            if($this->issueReturn($return) === false) {
                // Report incomplete return issuance and continue
                // Return failed, so what now?
                exit(1);
            }

            $this->setReturnAsProcessed($return['id']);
            $this->publishReturnWebhookEvent($return['id']);
        }

        return true;
    }

    private function issueReturn(array $return): bool
    {
        $success = $this->issueCreditAdjustment($return);
        // send data to RA queue for dispatch to RA return endpoint
        return $success;
    }

    private function setReturnAsProcessed(int $returnId)
    {
        $this->getDatabase()->query(<<<SQL
UPDATE transaction_item_return SET complete = 1 WHERE id = {$returnId} 
SQL
        );
    }

    private function publishReturnWebhookEvent($returnId)
    {
        $event = new Event();
        $event->setName('TransactionItemReturnWebhook.create');
        $event->setEntityId($returnId);
        $this->getEventPublisher()->publish(json_encode($event));
    }

    private function getEventPublisher()
    {
        $eventPublisherFactory = new EventPublisherFactory($this->container);
        return $eventPublisherFactory();
    }

    private function issueCreditAdjustment(array $return): bool
    {
        $participant = $this->getParticipantService()->getSingle($return['participant_unique_id']);
        $description = 'Return';
        if(!empty(trim($return['notes']))) {
            $description .= ': ' . $return['notes'];
        }

        return $this->getTransactionService()->adjustPoints(
            $participant,
            'credit',
            $return['total_return_amount'],
            $return['transaction_id'],
            $description,
            $return['guid'],
            null,
            $return['transactionItemId']
        ) !== null;
    }

    private function getPendingReturns(): array
    {
        $sql = <<<SQL
SELECT 
    (SELECT program.point FROM program WHERE program.id = participant.program_id) as program_point_value,
    participant.unique_id as participant_unique_id, 
    ((transactionproduct.retail + transactionproduct.handling + transactionproduct.shipping) * transactionitem.quantity) as total_return_amount,
    transactionitem.id as transactionItemId,
    transactionitem.transaction_id,
    transactionitem.guid,
    transaction_item_return.* 
FROM transaction_item_return 
JOIN transactionitem ON transaction_item_return.transaction_item_id = transactionitem.id
JOIN transactionproduct ON transactionitem.reference_id = transactionproduct.reference_id
JOIN `transaction` ON transaction_item_return.transaction_id = `transaction`.id
JOIN participant ON `transaction`.participant_id = participant.id
WHERE complete = 0
SQL;

        $sth = $this->getDatabase()->query($sql);
        return $sth->fetchAll();
    }

}

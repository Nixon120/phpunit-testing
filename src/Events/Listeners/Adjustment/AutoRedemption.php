<?php

namespace Events\Listeners\Adjustment;

use AllDigitalRewards\AMQP\MessagePublisher;
use Entities\Event;
use Entities\Program;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Participant\Transaction;
use Services\Participant\Participant;

class AutoRedemption extends AbstractListener
{
    /**
     * @var Participant
     */
    private $participantService;

    /**
     * @var Transaction
     */
    private $transactionService;

    public function __construct(
        MessagePublisher $publisher,
        Participant $participantService,
        Transaction $transactionService
    ) {
        parent::__construct($publisher);
        $this->participantService = $participantService;
        $this->transactionService = $transactionService;
    }

    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->autoRedeem($event);
    }

    private function autoRedeem(Event $event)
    {
        $participant = $this->participantService->getById($event->getEntityId());

        if ($participant === null) {
            $this->setError('Participant\'s Unique ID was changed before processing auto redemption queue');
            $event->setName('Adjustment.autoRedemption');
            $this->reQueueEvent($event);
            return false;
        }

        if($participant->getAddress() === null) {
            $this->setError('Participant\'s does not have an address');
            $event->setName('Adjustment.autoRedemption');
            $this->reQueueEvent($event);
            return false;
        }

        $program = $participant->getProgram();

        if ($this->isInstantAutoRedeemEnabled($program) === false
            || $this->isPanelistAutoRedeemEligible($participant) === false
            || $this->createAutoRedeemTransaction($participant) === true
        ) {
            return true;
        }


        $event->setName('Adjustment.autoRedemption');
        $this->reQueueEvent($event);
        return false;
    }

    private function createAutoRedeemTransaction(\Entities\Participant $participant)
    {
        $organizationId = $participant->getProgram()->getOrganization()->getId();
        $product = $participant->getProgram()->getAutoRedemption()->getProduct();
        $transaction = [
            'products' => [
                [
                    'sku' => $product->getSku(),
                    'quantity' => 1
                ]
            ],
            'shipping' => $participant->getAddress()->toArray(),
            'meta' => [
                [
                    'description' => 'AutoRedemption for ' . $product->getName()
                ]
            ]
        ];

        $transaction = $this->transactionService->insert(
            $organizationId,
            $participant->getUniqueId(),
            $transaction
        );
        if ($transaction instanceof \Entities\Transaction) {
            //@TODO: publish webhook
            return true;
        }

        $this->setError('Unable to create AutoRedemption Transaction');
        return false;
    }

    private function isInstantAutoRedeemEnabled(Program $program): bool
    {
        if ($program->getAutoRedemption() === null
            || $program->getAutoRedemption()->isActive() === false
            || $program->getAutoRedemption()->getInterval() === 'scheduled') {
            return false;
        }

        return true;
    }

    //@TODO with parallel processing, should we lock the participant ?
    private function isPanelistAutoRedeemEligible(\Entities\Participant $participant): bool
    {
        $product = $participant->getProgram()->getAutoRedemption()->getProduct();
        $multiplier = $participant->getProgram()->getPoint();

        if (($product->getPriceTotal() * $multiplier) <= $participant->getCredit()) {
            return true;
        }

        return false;
    }
}

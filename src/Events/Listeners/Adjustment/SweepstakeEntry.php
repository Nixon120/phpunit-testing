<?php

namespace Events\Listeners\Adjustment;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Participant\InputNormalizer;
use Entities\Event;
use Entities\Participant;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Participant\Participant as ParticipantService;
use Services\Participant\Balance as BalanceService;
use Services\Program\Sweepstake as SweepstakeService;

class SweepstakeEntry extends AbstractListener
{
    /**
     * @var ParticipantService
     */
    private $participantService;

    /**
     * @var BalanceService
     */
    private $balanceService;

    /**
     * @var SweepstakeService
     */
    private $sweepstakeService;

    /**
     * @var Participant
     */
    private $participant;

    public function __construct(
        MessagePublisher $publisher,
        ParticipantService $participantService,
        BalanceService $balanceService,
        SweepstakeService $sweepstakeService
    ) {
        parent::__construct($publisher);
        $this->participantService = $participantService;
        $this->balanceService = $balanceService;
        $this->sweepstakeService = $sweepstakeService;
    }

    public function handle(EventInterface $event)
    {
        /** @var Event $event */
        return $this->createEntry($event);
    }

    private function createEntry(Event $event)
    {
        $this->participant = $this->participantService->getById($event->getEntityId());

        if ($this->participant === null) {
            $this->setError('Participant\'s Unique ID was changed before processing auto redemption queue');
            $event->setName('Adjustment.sweepstakeEntry');
            $this->reQueueEvent($event);
            return false;
        }

        if ($this->isSweepstakeEligible() === false
            || ($this->isSweepstakeEligible() === true
                && $this->isParticipantSweepstakeEligible() === true
                && $this->createSweepstakeEntry() === true)) {
            return true;
        }

        $event->setName('Adjustment.sweepstakeEntry');
        $this->reQueueEvent($event);
        return false;
    }

    private function isSweepstakeEligible(): bool
    {
        $program = $this->participant->getProgram();
        $now = (new \DateTime)->format('Y-m-d');
        if ($program->getSweepstake() === null
            || $program->getSweepstake()->isActive() === false
            || $program->getSweepstake()->getStartDate() <= $now === false
            || $program->getSweepstake()->getEndDate() >= $now === false
            || $program->getSweepstake()->getType() === 'manual'
        ) {
            return false;
        }

        return true;
    }

    //@TODO with parallel processing, should we lock the participant ?
    private function isParticipantSweepstakeEligible(): bool
    {
        $entryCost = $this->participant->getProgram()->getSweepstake()->getPoint();
        if ($entryCost <= $this->participant->getCredit()) {
            return true;
        }

        return false;
    }

    private function createSweepstakeEntry()
    {
        //@TODO change this to support the new "createSweepstakeEntry" in the Sweepstakes Service
        // duplicate code reduction
        $entryCount = $this->getEntryCount();
        if ($entryCount === 0) {
            // No available entries, let's close up the event
            return true;
        }
        $pointCost = $this->getPointCost();
        $sweepstake = $this->participant->getProgram()->getSweepstake();
        //if you run getEntryCount after this, it will factor in the sweepstake drawings added below.
        //This will throw off pointCost.
        for ($i = 0; $i < $entryCount; $i++) {
            $entry = new \Entities\SweepstakeEntry;
            $entry->setParticipantId($this->participant->getId());
            $entry->setPoint($sweepstake->getPoint());
            $entry->setSweepstakeId($sweepstake->getId());

            //deduct point, insert entry
            if ($this->sweepstakeService->insertSweepstakeEntry($entry) === false) {
                $this->setError('Unable to create Sweepstake Entry');
                // if $i is greater then 0, some entries were made, and we should gracefully handle this..
                return false;
            }
        }

        $input = new InputNormalizer([
            'type' => 'debit',
            'amount' => $pointCost,
            'description' => $entryCount . ' x ' . $this->participant->getProgram()->getName() . ' sweepstakes',
            'reference' => 'Sweepstake'
        ]);
        $this->balanceService->createAdjustment($this->participant, $input);
        return true;
    }

    private function getEntryCount()
    {
        $sweepstake = $this->participant->getProgram()->getSweepstake();
        $existingEntryCount = $this->sweepstakeService->getParticipantEntryCount($sweepstake, $this->participant);
        $maxEntries = $sweepstake->getMaxParticipantEntry() - $existingEntryCount;
        $entryCount = $maxEntries;
        $pointCost = bcmul($maxEntries, $sweepstake->getPoint(), 2);

        if (bcsub($this->participant->getCredit(), $pointCost) < 0) {
            $entryCount = bcdiv($this->participant->getCredit(), $sweepstake->getPoint());
        }

        if ($sweepstake->getMaxParticipantEntry() < $entryCount) {
            $entryCount = $sweepstake->getMaxParticipantEntry();
        }

        return $entryCount;
    }

    private function getPointCost()
    {
        $entryCount = $this->getEntryCount();
        $sweepstake = $this->participant->getProgram()->getSweepstake();
        $pointCost = bcmul($entryCount, $sweepstake->getPoint(), 2);
        return $pointCost;
    }
}

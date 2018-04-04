<?php

namespace Services\Program;

use Controllers\Participant\InputNormalizer;
use Entities\Participant;
use Entities\SweepstakeDraw;
use Entities\SweepstakeEntry;
use Repositories\SweepstakeRepository;
use Services\Participant\Balance;
use Services\Program\Exception\SweepstakeServiceException;

class Sweepstake
{
    /**
     * @var SweepstakeRepository
     */
    public $repository;

    /**
     * @var Balance
     */
    public $balanceService;

    public function __construct(
        SweepstakeRepository $repository,
        Balance $balanceService
    ) {
        $this->repository = $repository;
        $this->balanceService = $balanceService;
    }

    public function createSweepstakeEntry(Participant $participant, $data)
    {
        $sweepstake = $participant->getProgram()->getSweepstake();
        if ($sweepstake->isActive() === false) {
            //Is service sweepstake eligible
            throw new SweepstakeServiceException(
                'Program ' . $participant->getProgram()->getUniqueId() . ' does not have an active sweepstake campaign'
            );
        }

        //Does participant have points available
        $entryCount = $data['entryCount'] ?? 1;
        $credit = $participant->getCredit();
        $pointCost = bcmul($sweepstake->getPoint(), $entryCount, 2);
        if ($credit < $pointCost) {
            throw new SweepstakeServiceException(
                'Participant does not have enough points for this transaction'
            );
        }

        $existingEntryCount = $this->getParticipantEntryCount($sweepstake, $participant);
        $availableEntries = $sweepstake->getMaxParticipantEntry() - ($existingEntryCount + $entryCount);

        if ($availableEntries < 0) {
            throw new SweepstakeServiceException(
                'Participant will exceed the maximum entry count for this sweepstake campaign'
            );
        }

        for ($i = 0; $i < $entryCount; $i++) {
            $entry = new \Entities\SweepstakeEntry;
            $entry->setParticipantId($participant->getId());
            $entry->setPoint($sweepstake->getPoint());
            $entry->setSweepstakeId($sweepstake->getId());

            //deduct point, insert entry
            if ($this->insertSweepstakeEntry($entry) === false) {
                // if $i is greater then 0, some entries were made, and we should gracefully handle this..
                // this would be the result of database failure
                throw new SweepstakeServiceException('Unable to create Sweepstake Entry');
            }
        }

        $input = new InputNormalizer([
            'type' => 'debit',
            'amount' => $pointCost,
            'description' => $entryCount . ' x ' . $participant->getProgram()->getName() . ' sweepstakes',
            'reference' => 'Sweepstake'
        ]);

        $this->balanceService->createAdjustment($participant, $input);
        return true;
    }

    public function insertSweepstakeEntry(SweepstakeEntry $entry): bool
    {
        return $this->repository->insertSweepstakeEntry($entry);
    }

    public function getParticipantEntryCount(\Entities\Sweepstake $sweepstake, Participant $participant)
    {
        return $this->repository->getEntriesBySweepstakeIdAndParticipantId($sweepstake->getId(), $participant->getId());
    }

    public function setDrawingEntries(): bool
    {
        $date = new \DateTime;
        $eligible = $this->repository->getPendingDrawingsByDate($date);
        if (!empty($eligible)) {
            foreach ($eligible as $drawing) {
                //@TODO let's do something with this if it fails
                $success = $this->repository->setSweepstakeDrawingEntries($drawing);
                if ($success === false) {
                    return false;
                }
            }
        }

        return true;
    }

    //update
    public function setConfiguration(\Entities\Program $program, ?array $configData = null): bool
    {
        if ($configData !== null) {
            $sweepstake = $this->buildEntities($program, $configData);
            return $this->repository->saveSweepstakeConfiguration($sweepstake);
        }

        return false;
    }

    private function buildEntities(\Entities\Program $program, array $data):\Entities\Sweepstake
    {
        $sweepstake = $this->buildSweepstakeEntity($program, $data);
        $sweepstake->setDrawing($this->buildSweepstakeDrawingEntity($program, $data));
        return $sweepstake;
    }

    private function buildSweepstakeEntity(\Entities\Program $program, array $data): \Entities\Sweepstake
    {
        $now = new \DateTime;
        $sweepstake = new \Entities\Sweepstake;
        if ((int)$data['active'] === 1) {
            $sweepstake = $program->getSweepstake();
            $start = new \DateTime($data['start_date']);
            $end = new \DateTime($data['end_date']);

            $sweepstake->setStartDate($start->format('Y-m-d'));
            $sweepstake->setEndDate($end->format('Y-m-d'));
            $sweepstake->setPoint($data['point']);
            $sweepstake->setMaxParticipantEntry($data['max_participant_entry']);
            $sweepstake->setActive(1);
            $sweepstake->setType($data['type']);
        }
        $sweepstake->setProgramId($program->getUniqueId());
        $sweepstake->setProgram($program);
        if ($sweepstake->getId() === null) {
            $sweepstake->setCreatedAt($now);
        }

        $sweepstake->setUpdatedAt($now);

        return $sweepstake;
    }

    private function buildSweepstakeDrawingEntity(\Entities\Program $program, array $data): array
    {
        $drawingContainer = [];
        if (!empty($data['draw_date']) && $data['draw_date'][0] !== "") {
            foreach ($data['draw_date'] as $key => $date) {
                $date = new \DateTime($date);
                $draw = new SweepstakeDraw;
                $draw->setDate($date->format('Y-m-d'));
                $draw->setDrawCount($data['draw_count'][$key]);
                $drawingContainer[] = $draw;
            }
        }

        return $drawingContainer;
    }
}

<?php

namespace Services\Program;

use AllDigitalRewards\Services\Catalog\Client;
use Entities\Participant;
use Entities\SweepstakeDraw;
use Entities\SweepstakeEntry;
use Repositories\SweepstakeRepository;
use Services\Participant\Transaction;
use Services\Program\Exception\SweepstakeServiceException;

class Sweepstake
{
    /**
     * @var SweepstakeRepository
     */
    public $repository;

    /**
     * @var Transaction
     */
    public $transactionService;

    /**
     * @var Client
     */
    private $productService;

    public function __construct(
        SweepstakeRepository $repository,
        Transaction $transactionService,
        Client $productService
    ) {
        $this->repository = $repository;
        $this->transactionService = $transactionService;
        $this->productService = $productService;
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

        //Does participant have available entries to redeem for.
        $entryCount = $data['entryCount'] ?? 1;
        $existingEntryCount = $this->getParticipantEntryCount($sweepstake, $participant);
        $availableEntries = $sweepstake->getMaxParticipantEntry() - ($existingEntryCount + $entryCount);

        if ($availableEntries < 0) {
            throw new SweepstakeServiceException(
                'Participant will exceed the maximum entry count for this sweepstake campaign'
            );
        }

        $product = $this->productService->getProduct($sweepstake->getSku());

        if (is_null($product)) {
            throw new SweepstakeServiceException(
                'Invalid Sweepstake product entry configuration.'
            );
        }

        $transaction = $this
            ->transactionService
            ->insertParticipantTransaction(
                $participant,
                [
                    'products' => [
                        [
                            'sku' => $sweepstake->getSku(),
                            'quantity' => $entryCount
                        ]
                    ],
                    'issue_points' => !empty($data['issue_points']) && $data['issue_points'] === true ? true : false
                ]
            );

        if (is_null($transaction)) {
            throw new SweepstakeServiceException(
                implode(", ", $this->transactionService->getTransactionRepository()->getErrors())
            );
        }

        for ($i = 0; $i < $entryCount; $i++) {
            $entry = new \Entities\SweepstakeEntry;
            $entry->setParticipantId($participant->getId());
            $entry->setPoint(
                bcmul(
                    $product->getPriceTotal(),
                    $participant->getProgram()->getPoint()
                )
            );
            $entry->setSweepstakeId($sweepstake->getId());

            //deduct point, insert entry
            if ($this->insertSweepstakeEntry($entry) === false) {
                // if $i is greater then 0, some entries were made, and we should gracefully handle this..
                // this would be the result of database failure
                throw new SweepstakeServiceException('Unable to create Sweepstake Entry');
            }
        }

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

    private function buildEntities(\Entities\Program $program, array $data): \Entities\Sweepstake
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
            $sweepstake->setSku($data['sku']);
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
        if (!empty($data['drawings'])) {
            foreach ($data['drawings'] as $drawing) {
                $date = new \DateTime($drawing['date']);
                $draw = new SweepstakeDraw;
                $draw->setDate($date->format('Y-m-d'));
                $draw->setDrawCount($drawing['draw_count']);
                $draw->setAltEntry($drawing['alt_entry']);
                $drawingContainer[] = $draw;
            }
        }
        
        return $drawingContainer;
    }
}

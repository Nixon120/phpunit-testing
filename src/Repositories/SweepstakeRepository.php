<?php

namespace Repositories;

use Entities\Program;
use Entities\Sweepstake;
use Entities\SweepstakeDraw;
use Entities\SweepstakeEntry;

class SweepstakeRepository extends BaseRepository
{
    protected $table = 'Sweepstake';

    public function getRepositoryEntity()
    {
        // TODO: Implement getRepositoryEntity() method.
    }

    public function insertSweepstakeEntry(SweepstakeEntry $entry): bool
    {
        $this->table = 'SweepstakeEntry';
        $success = $this->place($entry);
        $this->table = 'Sweepstake';
        return $success;
    }

    public function saveSweepstakeConfiguration(Sweepstake $sweepstake): bool
    {

        if ($this->place($sweepstake) === false) {
            return false;
        }

        $activeDrawings = $sweepstake->getDrawing();

        if ($sweepstake->isActive() && !empty($activeDrawings)) {
            $sweepstakeId = $this->database->lastInsertId();
            $this->table = 'SweepstakeDraw';
            $activeDrawingDates = [];
            foreach ($activeDrawings as $drawing) {
                $drawing->setSweepstakeId($sweepstakeId);
                if ($this->place($drawing) === false) {
                    return false;
                }

                $activeDrawingDates[] = $drawing->getDate();
            }

            $this->removeDeletedDates($sweepstakeId, $activeDrawingDates);
            $this->table = 'Sweepstake';
        }

        return true;
    }

    public function getSweepstake(Program $program)
    {
        $sql = "SELECT Sweepstake.* FROM `Sweepstake` WHERE program_id = ?";
        $args = [$program->getUniqueId()];
        if (!$sweepstake = $this->query($sql, $args, Sweepstake::class)) {
            return null;
        }

        return $this->hydrateSweepstake($sweepstake);
    }

    public function getSweepstakeById($sweepstakeId): ?Sweepstake
    {
        $sql = "SELECT Sweepstake.* FROM `Sweepstake` WHERE `Sweepstake`.id = ?";
        $args = [$sweepstakeId];
        if (!$sweepstake = $this->query($sql, $args, Sweepstake::class)) {
            return null;
        }

        return $sweepstake;
    }

    private function hydrateSweepstake(Sweepstake $sweepstake)
    {
        $sql = "SELECT * FROM `SweepstakeDraw` WHERE sweepstake_id = ?";
        $args = [$sweepstake->getId()];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $drawings = $sth->fetchAll(\PDO::FETCH_CLASS, SweepstakeDraw::class);
        if ($drawings) {
            $sweepstake->setDrawing($drawings);
        }

        return $sweepstake;
    }

    private function removeDeletedDates($sweepstakeId, array $dates): bool
    {
        if (empty($dates)) {
            return true;
        }

        $placeholder = rtrim(str_repeat('?, ', count($dates)), ', ');
        $query = <<<SQL
DELETE FROM SweepstakeDraw 
WHERE SweepstakeDraw.sweepstake_id = ? 
  AND date NOT IN ({$placeholder})
SQL;

        $sth = $this->database->prepare($query);
        $args = [];
        array_push($args, $sweepstakeId);
        $args = array_merge($args, $dates);
        return $sth->execute($args);
    }

    public function getPendingDrawingsByDate(\DateTime $date)
    {
        $sql = "SELECT * FROM `SweepstakeDraw` WHERE date = ? AND processed = 0";
        $args = [$date->format('Y-m-d')];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $drawings = $sth->fetchAll(\PDO::FETCH_CLASS, SweepstakeDraw::class);
        if ($drawings) {
            return $drawings;
        }

        return [];
    }

    private function getPreviousDrawingDateFromCurrentDate($sweepstakeId, $currentDate)
    {
        $sql = "SELECT * FROM `SweepstakeDraw` WHERE `sweepstake_id` = ? AND `date` < ? ORDER BY `date` DESC LIMIT 1";
        $args = [$sweepstakeId, $currentDate];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $sth->setFetchMode(\PDO::FETCH_CLASS, SweepstakeDraw::class);
        /** @var SweepstakeDraw $draw */
        $draw = $sth->fetch();

        if ($draw) {
            // + 1, to prevent ON the date of entries ?
            return $draw->getDate();
        }

        //get sweepstake open range date
        $sweepstake = $this->getSweepstakeById($sweepstakeId);
        return $sweepstake->getStartDate();
    }

    public function setSweepstakeDrawingEntries(SweepstakeDraw $drawing): bool
    {
        $eligibleEntryStartDate = $this->getPreviousDrawingDateFromCurrentDate(
            $drawing->getSweepstakeId(),
            $drawing->getDate()
        );
        $eligibleEntryEndDate = (new \DateTime('-1 day'))->format('Y-m-d');
        $sql = <<<SQL
UPDATE SweepstakeEntry SET sweepstake_draw_id = ? 
WHERE sweepstake_id = ? 
  AND DATE(SweepstakeEntry.created_at) >= ? AND DATE(SweepstakeEntry.created_at) <= ?
ORDER BY RAND() 
LIMIT {$drawing->getDrawCount()}
SQL;
        $args = [$drawing->getId(), $drawing->getSweepstakeId(), $eligibleEntryStartDate, $eligibleEntryEndDate];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $sql = <<<SQL
UPDATE SweepstakeEntry SET sweepstake_alt_draw_id = ? 
WHERE sweepstake_id = ? 
  AND DATE(SweepstakeEntry.created_at) >= ? AND DATE(SweepstakeEntry.created_at) <= ? 
  AND SweepstakeEntry.sweepstake_draw_id IS NULL
ORDER BY RAND() 
LIMIT {$drawing->getAltEntry()}
SQL;

        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        $sql = <<<SQL
UPDATE SweepstakeDraw SET processed = 1 WHERE id = ?
SQL;
        $args = [$drawing->getId()];
        $sth = $this->database->prepare($sql);
        $sth->execute($args);

        return true;
    }

    public function getEntriesBySweepstakeIdAndParticipantId($sweepstakeId, $participantId)
    {
        $sql = "SELECT count(id) AS entries FROM `SweepstakeEntry` WHERE sweepstake_id = ? AND participant_id = ? GROUP BY participant_id";
        $args = [$sweepstakeId, $participantId];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $sth->fetch();
        if ($result) {
            return $result['entries'];
        }

        return 0;
    }
}

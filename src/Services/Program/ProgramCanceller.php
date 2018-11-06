<?php

namespace Services\Program;

use \PDO as PDO;
use Repositories\ProgramRepository;

class ProgramCanceller
{
    /**
     * @var ProgramRepository
     */
    public $repository;

    public function __construct(
        ProgramRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function cancelExpiredPrograms() {
        $expiredPrograms = $this->getExpiredPrograms();
        if (count($expiredPrograms) > 0) {
            foreach($expiredPrograms as $program) {
                $this->repository->updatePublishColumn($program->getUniqueId(), 0);
            }
        }
    }

    private function getExpiredPrograms() {
        $database = $this->repository->getDatabase();
        $today = date("Y-m-d");
        $sql = "SELECT * FROM program WHERE DATE_ADD(end_date, INTERVAL grace_period DAY) < '" . $today . "';";
        $sth = $database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_CLASS, $this->repository->getRepositoryEntity());
    }

}

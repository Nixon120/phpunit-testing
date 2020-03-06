<?php

namespace Services\Program;

use \PDO as PDO;
use Repositories\ProgramRepository;
use Services\CacheService;

class ProgramCanceller
{
    /**
     * @var ProgramRepository
     */
    public $repository;
    /**
     * @var CacheService
     */
    public $cacheService;

    public function __construct(
        ProgramRepository $repository,
        CacheService $cacheService
    ) {
        $this->repository = $repository;
        $this->cacheService = $cacheService;
    }

    public function cancelExpiredPrograms()
    {
        $expiredPrograms = $this->getExpiredPrograms();
        if (count($expiredPrograms) > 0) {
            foreach ($expiredPrograms as $program) {
                $this->repository->cancelProgram($program->getUniqueId());

                //set this for services looking for parent program update
                $this->cacheService->cacheItem($program->getUniqueId(), $program->getUniqueId() . '_expired');

                //need to clear cache if exists
                $programUrl = $this->getProgramSubDomainAndDomain($program->getUniqueId());
                $url = strtolower($programUrl);
                if ($this->cacheService->cachedItemExists($url) === true) {
                    $this->cacheService->clearItem($url);
                }
            }
        }
    }

    private function getExpiredPrograms()
    {
        $database = $this->repository->getDatabase();
        $sql = "SELECT * FROM program WHERE DATE_ADD(end_date, INTERVAL IFNULL(grace_period,0) DAY) <= NOW() AND (active = 1 OR published = 1)";
        $sth = $database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_CLASS, $this->repository->getRepositoryEntity());
    }

    /**
     * @param $unique_id
     * @return mixed
     */
    private function getProgramSubDomainAndDomain($unique_id)
    {
        $sql = <<<SQL
SELECT CONCAT(Program.url, '.', Domain.url) as url
FROM `Program`
LEFT JOIN `Domain` ON Domain.id = Program.domain_id
WHERE Program.unique_id = ?
SQL;

        $args = [$unique_id];
        $database = $this->repository->getDatabase();
        $sth = $database->prepare($sql);
        $sth->execute($args);
        return $sth->fetchColumn(0);
    }
}

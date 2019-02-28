<?php

namespace Services\Program;

use \PDO as PDO;
use Psr\Container\ContainerInterface;
use Repositories\ProgramRepository;
use Entities\OneTimeAutoRedemption;
use Services\Scheduler\Tasks\OneTimeScheduledRedemption;

class OneTimeAutoRedemptionProcessor
{
    /**
     * @var ProgramRepository
     */
    public $repository;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ProgramRepository $repository,
        ContainerInterface $container
    ) {
        $this->repository = $repository;
        $this->container = $container;
    }

    public function run() {
        $oneTimeAutoRedemptions = $this->getOneTimeAutoRedmeptions();
        foreach($oneTimeAutoRedemptions as $redemption) {
            $scheduledRedemptionTask = new OneTimeScheduledRedemption();
            $scheduledRedemptionTask->setAutoRedemption($redemption);
            $scheduledRedemptionTask->setContainer($this->container);
            $scheduledRedemptionTask->run();
        }
    }

    private function getOneTimeAutoRedmeptions() {
        $database = $this->repository->getDatabase();
        $today = date("Y-m-d");
        $sql = "SELECT * FROM onetimeautoredemption WHERE redemption_date = '" . $today . "' AND active = 1;";
        $sth = $database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_CLASS, OneTimeAutoRedemption::class);
    }

}

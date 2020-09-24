<?php

namespace Services\Program;

use Entities\AutoRedemption;
use \PDO as PDO;
use Psr\Container\ContainerInterface;
use Repositories\ProgramRepository;
use Entities\OneTimeAutoRedemption;
use Services\Scheduler\Tasks\OneTimeScheduledRedemption;
use Traits\LoggerAwareTrait;

class OneTimeAutoRedemptionProcessor
{
    use LoggerAwareTrait;
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

    public function run()
    {
        /** @var OneTimeAutoRedemption[] $oneTimeAutoRedemptions */
        $oneTimeAutoRedemptions = $this->getOneTimeAutoRedmeptions();

        foreach ($oneTimeAutoRedemptions as $oneTimeAutoRedemption) {
            if ($this->isProgramActiveAndNotExpired($oneTimeAutoRedemption) === false) {
                $oneTimeAutoRedemption->setActive(0);
                $this->repository->place($oneTimeAutoRedemption);
            } else {
                $scheduledRedemptionTask = new OneTimeScheduledRedemption();
                $scheduledRedemptionTask->setAutoRedemption($oneTimeAutoRedemption);
                $scheduledRedemptionTask->setContainer($this->container);
                $scheduledRedemptionTask->run();
            }
        }
    }

    private function getOneTimeAutoRedmeptions()
    {
        $database = $this->repository->getDatabase();
        $today = date("Y-m-d");
        $sql = "SELECT * FROM onetimeautoredemption WHERE redemption_date = '" . $today . "' AND active = 1;";
        $sth = $database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_CLASS, OneTimeAutoRedemption::class);
    }

    /**
     * @param OneTimeAutoRedemption $oneTimeAutoRedemption
     * @return bool
     */
    private function isProgramActiveAndNotExpired(OneTimeAutoRedemption $oneTimeAutoRedemption)
    {
        $program = $this->repository->getProgram(
            $oneTimeAutoRedemption->getProgramId(),
            false
        );

        if ($program === null) {
            $this->getLogger()->error(
                'OneTimeAutoRedemption Scheduler- Program does not exist.',
                [
                    'action' => 'get',
                    'onetimeautoredemption_id' => $oneTimeAutoRedemption->getId(),
                    'success' => false,
                ]
            );
            return false;
        }

        return $program->isActiveAndNotExpired();
    }
}

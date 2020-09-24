<?php
namespace Services\Scheduler;

use pmill\Scheduler\TaskList;
use Psr\Container\ContainerInterface;
use Repositories\ProgramRepository;
use Repositories\SchedulerRepository;

class ServiceFactory
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getService(): AutoRedemptionScheduler
    {
        $repository = new SchedulerRepository(
            $this->container->get('database')
        );

        $tasks = new TaskList;

        $scheduler = new AutoRedemptionScheduler(
            $repository,
            $tasks,
            $this->container
        );

        $scheduler->setProgramRepository($this->getProgramRepo());

        return $scheduler;
    }

    /**
     * @return ProgramRepository
     */
    private function getProgramRepo(): ProgramRepository
    {
        $factory = new \Services\Program\ServiceFactory($this->container);
        return $factory->getProgramRepository();
    }
}

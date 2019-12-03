<?php
namespace Services\Scheduler;

use Psr\Container\ContainerInterface;
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

        $tasks = new \pmill\Scheduler\TaskList;

        return new \Services\Scheduler\AutoRedemptionScheduler(
            $repository,
            $tasks,
            $this->container
        );
    }
}

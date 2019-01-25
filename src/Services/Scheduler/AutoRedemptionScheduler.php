<?php

namespace Services\Scheduler;

use Entities\AutoRedemption;
use Entities\Task;
use pmill\Scheduler as Schedule;
use Psr\Container\ContainerInterface;
use Repositories\SchedulerRepository;
use Services\Scheduler\Tasks\ScheduledRedemption;
use Stringy\Stringy;

class AutoRedemptionScheduler
{
    const TASK_PATH = ROOT . '/src/Services/Scheduler/Tasks';

    const NAMESPACE = __NAMESPACE__ . '\\Tasks\\';

    /**
     * @var SchedulerRepository
     */
    public $repository;

    /**
     * @var Schedule\TaskList
     */
    public $schedule;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        SchedulerRepository $repository,
        Schedule\TaskList $schedule,
        ContainerInterface $container
    ) {
        $this->repository = $repository;
        $this->schedule = $schedule;
        $this->container = $container;
    }

    /**
     * This method will return key value pairs for Task::class names and their user friendly name
     * @return array
     */
    public function getAllTasks(): array
    {
        $availableTaskFilenames = array_diff(scandir(self::TASK_PATH), ['.', '..']);

        $taskList = [];
        foreach ($availableTaskFilenames as $filename) {
            $taskList = array_merge($taskList, $this->taskFilenameToArray($filename));
        }

        return $taskList;
    }

    private function taskFilenameToArray(string $filename)
    {
        $task = new Stringy($filename);
        $userFriendlyName = (string)$task
            ->removeRight('.php')
            ->delimit(' ')
            ->upperCamelize();

        $className = (string)$task
            ->removeRight('.php')
            ->prepend(self::NAMESPACE);

        return [
            $userFriendlyName => $className
        ];
    }

    /**
     * This method will be invoked when the scheduler cron is ran. This will grab all "active" tasks
     * @param AutoRedemption $autoRedemption
     * @return bool
     */
    private function queueTask(AutoRedemption $autoRedemption): bool
    {
        $scheduledRedemptionTask = new ScheduledRedemption();
        $scheduledRedemptionTask->setAutoRedemption($autoRedemption);
        $scheduledRedemptionTask->setExpression(
            $autoRedemption->getCronExpression()
        );
        $scheduledRedemptionTask->setContainer($this->container);
        $this->schedule->addTask($scheduledRedemptionTask);
        return true;
    }

    private function prepareTaskQueue()
    {
        $filter = new FilterNormalizer([
            'interval' => 1, // Scheduled
            'active' => 1
        ]);

        $tasks = $this
            ->repository
            ->getCollection($filter, 0, 1000);

        if (empty($tasks)) {
            return;
        }

        foreach ($tasks as $task) {
            $this->queueTask($task);
        }
    }

    public function run()
    {
        $this->prepareTaskQueue();
        $this->schedule->run();
        return $this->schedule->getOutput();
    }
}

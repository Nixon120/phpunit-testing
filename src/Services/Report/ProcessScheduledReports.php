<?php

namespace Services\Report;

use \PDO as PDO;
use Repositories\ReportRepository;
use Entities\Event;

class ProcessScheduledReports
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        $factory
    ) {
        $this->factory = $factory;
        $this->repository = $this->factory->getReportRepository();
    }

    public function processReports()
    {
        $reports = $this->getReportsToBeProcessed();
        if (count($reports) > 0) {
            foreach ($reports as $report) {
                $service = $this->getReportService($report);
                $service->queueReportEvent($report);
            }
        }
    }

    private function getReportsToBeProcessed()
    {
        $database = $this->repository->getDatabase();
        $today = date("Y-m-d");
        $sql = "SELECT * FROM report 
                WHERE report_date <= '" . $today . "' 
                AND report_date IS NOT NULL
                AND processed = 0;";
        $sth = $database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_CLASS, $this->repository->getRepositoryEntity());
    }

    function getReportService($report)
    {
        $reportClass = '\\Services\\Report\\' . $report->getReportClass();
        /** @var Reportable $service */
        $service = new $reportClass($this->factory);
        return $service;
    }
}

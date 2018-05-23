<?php

abstract class AbstractReportTest extends \PHPUnit\Framework\TestCase
{
    private $mockServiceFactory;

    private $mockReportRepository;

    private $mockEventPublisher;

    private $mockDatabase;

    protected function getMockReportEntity()
    {
        $report = new \Entities\Report;
        $report->setOrganization('alldigitalrewards');
        $report->setProgram('adr');
        $report->setReport(1);
        $report->setFormat('csv');
        $report->setParameters(
            '{"organization":"alldigitalrewards","program":"adr","status":1,"start_date":"2017-01-01","end_date":"2018-01-01","unique_id":"123","firstname":"Hello","lastname":"World","address1":"123 Acme St","address2":"Suite #3","fields":["Participant.created_at","Participant.unique_id","Participant.firstname"]}'
        );

        return $report;
    }

    protected function getPdoStatementMock()
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "fetchAll", "setFetchMode"])
            ->getMock();
    }

    protected function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods(["getLastInsertId", "getReportById", "place", "prepare"])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Services\Report\ServiceFactory
     */
    protected function getMockServiceFactory(): \Services\Report\ServiceFactory
    {
        if ($this->mockServiceFactory === null) {
            $this->mockServiceFactory = $this
                ->getMockBuilder(\Services\Report\ServiceFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(["getDatabase", "getReportRepository", "getEventPublisher"])
                ->getMock();
        }

        return $this->mockServiceFactory;
    }

    protected function getMockReportRepository()
    {
        if ($this->mockReportRepository === null) {
            $this->mockReportRepository = $this
                ->getMockBuilder(\Repositories\ReportRepository::class)
                ->disableOriginalConstructor()
                ->setMethods(["place", "getReportById", "getLastInsertId"])
                ->getMock();
        }

        return $this->mockReportRepository;
    }

    protected function getMockEventPublisher()
    {
        if ($this->mockEventPublisher === null) {
            $this->mockEventPublisher = $this
                ->getMockBuilder(\AllDigitalRewards\AMQP\MessagePublisher::class)
                ->disableOriginalConstructor()
                ->setMethods(["publish"])
                ->getMock();
        }

        return $this->mockEventPublisher;
    }
}

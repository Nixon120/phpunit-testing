<?php

class ReportRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public $mockDatabase;

    public $mockReportEntity;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\PDOStatement
     */
    private function getPdoStatementMock()
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "fetchAll", "setFetchMode"])
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\PDO
     */
    private function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    /**
     * @return \Repositories\ReportRepository
     */
    private function getReportRepository()
    {
        $repository = new \Repositories\ReportRepository($this->getMockDatabase());
        $repository->setOrganizationIdContainer(['alldigitalrewards']);

        return $repository;
    }

    /**
     * @return \Entities\Report
     */
    private function getMockReport()
    {
        if ($this->mockReportEntity === null) {
            $this->mockReportEntity = new \Entities\Report;
            //Enrollment type
            $this->mockReportEntity->setReport(1);
        }

        return $this->mockReportEntity;
    }

    /**
     * @return \Entities\Report[]
     */
    private function getMockReportCollection()
    {
        return [
            $this->getMockReport()
        ];
    }

    private function getMockReportArrayCollection()
    {
        $container = [];
        foreach ($this->getMockReportCollection() as $key => $report) {
            $container[] = $report->toArray();
            $container[$key]['parameters'] = json_decode($container[$key]['parameters'], true);
            $container[$key]['report'] = $report->getReportName();
        }

        return $container;
    }

    /**
     * Test fetching a single report
     */
    public function testGetReportById()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, \Entities\Report::class);

        $sthMock->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->getMockReport()));

        $repository = $this->getReportRepository();
        $this->assertEquals($repository->getReportById(123), $this->getMockReport());
    }

    /**
     * Test fetching a single report
     */
    public function testGetReportByIdThatDoesntExist()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, \Entities\Report::class);

        $sthMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $repository = $this->getReportRepository();
        $this->assertEquals($repository->getReportById(123), null);
    }


    /**
     * Test fetching a single report
     */
    public function testGetReportList()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, \Entities\Report::class)
            ->will($this->returnValue($this->getMockReportCollection()));

        $repository = $this->getReportRepository();

        $filterNormalizer = new \Services\Report\ReportFilterNormalizer([
            'organization' => 'alldigitalrewards'
        ]);

        $this->assertEquals($this->getMockReportArrayCollection(), $repository->getReportList($filterNormalizer, 0, 100));
    }
}

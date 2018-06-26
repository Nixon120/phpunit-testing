<?php

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class SweepstakeReportTest extends AbstractReportTest
{
    private $reportService;

    public function getReportService()
    {
        if ($this->reportService === null) {
            $this->reportService = new \Services\Report\Sweepstake;
            $this->reportService->setFactory($this->getMockServiceFactory());
        }

        return $this->reportService;
    }

    public function getInputNormalizer()
    {
        $inputNormalizer = new \Controllers\Report\InputNormalizer(
            [
                'organization' => 'alldigitalrewards',
                'program' => 'adr',
                'start_date' => '2017-01-01',
                'end_date' => '2018-01-01',
                'fields' => $this->getSweepstakeFields()
            ]
        );

        return $inputNormalizer;
    }

    public function getSweepstakeFields()
    {
        return [
            'Participant.unique_id',
            'Participant.firstname',
            'Participant.lastname'
        ];
    }

    public function testGetReportName()
    {
        $report = $this->getReportService();
        $this->assertEquals('Sweepstake Drawings', $report->getReportName());
    }

    public function testGetReportClassification()
    {
        $report = $this->getReportService();
        $this->assertEquals(6, $report->getReportClassification());
    }

    public function testGetReportData()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue([]));

        $sthMock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(100));

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $filter = new \Services\Report\SweepstakeFilterNormalizer($inputNormalizer->getInput());
        $report->setInputNormalizer($inputNormalizer);
        $report->setFilter($filter);

        $reportResponse = $report->getReportData();

        $this->assertInstanceOf(ReportDataResponse::class, $reportResponse);
        $this->assertSame(100,$reportResponse->getTotalRecords());
    }

}

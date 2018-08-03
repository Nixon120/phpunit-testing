<?php

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class PointBalanceReportTest extends AbstractReportTest
{
    private $reportService;

    public function getReportService()
    {
        if ($this->reportService === null) {
            $this->reportService = new \Services\Report\PointBalance;
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
                'status' => 1,
                'start_date' => '2017-01-01',
                'end_date' => '2018-01-01',
                'unique_id' => '123',
                'firstname' => 'Hello',
                'lastname' => 'World',
                'address1' => '123 Acme St',
                'address2' => 'Suite #3',
                'fields' => $this->getParticipantBalanceFields()
            ]
        );

        return $inputNormalizer;
    }

    public function getParticipantBalanceFields()
    {
        return [
            'Participant.email_address',
            'Participant.unique_id'
        ];
    }

    public function testGetReportName()
    {
        $report = $this->getReportService();
        $this->assertEquals('Participant Point Balance', $report->getReportName());
    }

    public function testGetReportClassification()
    {
        $report = $this->getReportService();
        $this->assertEquals(5, $report->getReportClassification());
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
            ->will($this->returnValue(0));

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $filter = new \Services\Report\PointBalanceFilterNormalizer($inputNormalizer->getInput());
        $report->setInputNormalizer($inputNormalizer);
        $report->setFilter($filter);

        $reportResponse = $report->getReportData();

        $this->assertInstanceOf(ReportDataResponse::class, $reportResponse);
        $this->assertSame(0,$reportResponse->getTotalRecords());
    }

    public function testGetReportMetaFields()
    {
        $report = $this->getReportService();
        $filterNormalizer = new \Services\Report\ReportFilterNormalizer([
            'organization' => 'alldigitalrewards',
            'program' => 'alldigitalrewards'
        ]);
        $report->setFilter($filterNormalizer);

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(1))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(1))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(1))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls([['key' => 'yep']]);

        $this->getMockServiceFactory()
            ->expects($this->exactly(1))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $expected = [
            'participant' => ['yep']
        ];

        $this->assertEquals($expected, $report->getReportMetaFields());
    }
}

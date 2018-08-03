<?php

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class TransactionReportTest extends AbstractReportTest
{
    private $reportService;

    public function getReportService()
    {
        if ($this->reportService === null) {
            $this->reportService = new \Services\Report\Transaction;
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
                'fields' => $this->getTransactionFields()
            ]
        );

        return $inputNormalizer;
    }

    public function getTransactionFields()
    {
        return [
            'Program.unique_id as program_uuid',
            'Program.name as program_name'
        ];
    }

    public function testGetReportName()
    {
        $report = $this->getReportService();
        $this->assertEquals('Participant Transaction', $report->getReportName());
    }

    public function testGetReportClassification()
    {
        $report = $this->getReportService();
        $this->assertEquals(2, $report->getReportClassification());
    }

    public function testGetReportMetaFields()
    {
        $report = $this->getReportService();
        $filterNormalizer = new \Services\Report\ReportFilterNormalizer([
            'organization' => 'alldigitalrewards',
            'program' => 'alldigitalrewards'
        ]);
        $inputNormalizer = new \Controllers\Report\InputNormalizer([
            'meta' => [
                'participant' => [
                    'yep'
                ],
                'transaction' => [
                    'yolo'
                ]
            ]
        ]);
        $report->setFilter($filterNormalizer);
        $report->setInputNormalizer($inputNormalizer);

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls([['key' => 'yolo']], [['key' => 'yep']]);

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $expected = [
            'transaction' => ['yolo'],
            'participant' => ['yep']
        ];

        $this->assertEquals($expected, $report->getReportMetaFields());
    }

    public function testGetReportMetaFieldsWhereNoneExist()
    {
        $report = $this->getReportService();
        $filterNormalizer = new \Services\Report\ReportFilterNormalizer([
            'organization' => 'alldigitalrewards',
            'program' => 'alldigitalrewards',
        ]);
        $report->setFilter($filterNormalizer);

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(false, false);

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $expected = [
            'transaction' => [],
            'participant' => []
        ];

        $this->assertEquals($expected, $report->getReportMetaFields());
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
            ->will($this->returnValue(345));

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $filter = new \Services\Report\TransactionFilterNormalizer($inputNormalizer->getInput());
        $report->setInputNormalizer($inputNormalizer);
        $report->setFilter($filter);

        $reportResponse = $report->getReportData();

        $this->assertInstanceOf(ReportDataResponse::class, $reportResponse);
        $this->assertSame(345,$reportResponse->getTotalRecords());
    }
}

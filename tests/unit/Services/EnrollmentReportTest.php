<?php

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class EnrollmentReportTest extends AbstractReportTest
{
    private $reportService;

    public function getReportService()
    {
        if ($this->reportService === null) {
            $this->reportService = new \Services\Report\Enrollment;
            $this->reportService->setFactory($this->getMockServiceFactory());
        }

        return $this->reportService;
    }

    public function testSetAndGetFields()
    {
        $fields = [
            'Participant.created_at',
            'Participant.unique_id',
            'Participant.firstname'
        ];

        $inputNormalizer = new \Controllers\Report\InputNormalizer(
            [
                'fields' => $fields
            ]
        );

        $reportService = $this->getReportService();
        $reportService->setInputNormalizer($inputNormalizer);
        $this->assertEquals($fields, $reportService->getFields());
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
                'fields' => $this->getEnrollmentFields()
            ]
        );

        return $inputNormalizer;
    }

    public function getEnrollmentFields()
    {
        return [
            'Participant.created_at',
            'Participant.unique_id',
            'Participant.firstname'
        ];
    }

    public function testSetAndGetFilter()
    {
        $inputNormalizer = $this->getInputNormalizer();

        $filter = new \Services\Report\EnrollmentFilterNormalizer($inputNormalizer->getInput());

        $report = $this->getReportService();
        $report->setFilter($filter);

        $this->assertEquals($filter, $report->getFilter());
    }

    public function testSetAndGetOffset()
    {
        $report = $this->getReportService();
        $report->setOffset(100);
        $this->assertEquals(100, $report->getOffset());
    }

    public function testSetAndGetPage()
    {
        $report = $this->getReportService();
        $report->setPage(2);
        $this->assertEquals(2, $report->getPage());
    }

    public function testGetReportName()
    {
        $report = $this->getReportService();
        $this->assertEquals('Participant Enrollment', $report->getReportName());
    }

    public function testGetReportClassification()
    {
        $report = $this->getReportService();
        $this->assertEquals(1, $report->getReportClassification());
    }

    public function testGetReportHeaders()
    {
        $fields = [
            'Participant.created_at',
            'Participant.unique_id',
            'Participant.firstname'
        ];

        $inputNormalizer = new \Controllers\Report\InputNormalizer(
            [
                'fields' => $fields
            ]
        );

        $reportService = $this->getReportService();
        $reportService->setInputNormalizer($inputNormalizer);
        $this->assertEquals(
            [
                'Registration Date',
                'Participant ID',
                'First Name'
            ],
            $reportService->getReportHeaders()
        );
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
            ->will($this->returnValue(10));

        $this->getMockServiceFactory()
            ->expects($this->exactly(2))
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $filter = new \Services\Report\EnrollmentFilterNormalizer($inputNormalizer->getInput());
        $report->setInputNormalizer($inputNormalizer);
        $report->setFilter($filter);

        $reportResponse = $report->getReportData();

        $this->assertInstanceOf(ReportDataResponse::class, $reportResponse);
        $this->assertSame(10,$reportResponse->getTotalRecords());
    }

    public function testFieldDoesntExist()
    {
        $report = $this->getReportService();
        $inputNormalizer = new \Controllers\Report\InputNormalizer(
            [
                'fields' => [
                    'yolo'
                ]
            ]
        );
        $this->assertFalse($report->setInputNormalizer($inputNormalizer));
    }

    public function testRequestGeneration()
    {
        $this->getMockServiceFactory()
            ->expects($this->once())
            ->method('getReportRepository')
            ->willReturn($this->getMockReportRepository());

        $this->getMockServiceFactory()
            ->expects($this->once())
            ->method('getEventPublisher')
            ->willReturn($this->getMockEventPublisher());

        $this->getMockReportRepository()
            ->expects($this->once())
            ->method('getLastInsertId')
            ->willReturn(1);

        $this->getMockReportRepository()
            ->expects($this->once())
            ->method('place')
            ->with($this->getMockReportEntity())
            ->willReturn(true);

        $oReport = $this->getMockReportEntity();
        $oReport->setId(1);
        $this->getMockReportRepository()
            ->expects($this->once())
            ->method('getReportById')
            ->with(1)
            ->willReturn($oReport);

        $this->getMockEventPublisher()
            ->expects($this->once())
            ->method('publish')
            ->with($this->isType('string'));

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $report->setInputNormalizer($inputNormalizer);
        $this->assertSame($oReport, $report->request());
    }
}

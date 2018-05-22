<?php

class RedemptionReportTest extends AbstractReportTest
{
    private $reportService;

    public function getReportService()
    {
        if ($this->reportService === null) {
            $this->reportService = new \Services\Report\Redemption;
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
                'fields' => $this->getRedemptionFields()
            ]
        );

        return $inputNormalizer;
    }

    public function getRedemptionFields()
    {
        return [
            'Program.unique_id as program_uuid',
            'Program.name as program_name',
            'Transaction.created_at'
        ];
    }

    public function testGetReportName()
    {
        $report = $this->getReportService();
        $this->assertEquals('Participant Redemption', $report->getReportName());
    }

    public function testGetReportClassification()
    {
        $report = $this->getReportService();
        $this->assertEquals(3, $report->getReportClassification());
    }

    public function testGetReportData()
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
            ->will($this->returnValue([]));

        $this->getMockServiceFactory()
            ->expects($this->once())
            ->method('getDatabase')
            ->willReturn($this->getMockDatabase());

        $report = $this->getReportService();

        $inputNormalizer = $this->getInputNormalizer();
        $filter = new \Services\Report\RedemptionFilterNormalizer($inputNormalizer->getInput());
        $report->setInputNormalizer($inputNormalizer);
        $report->setFilter($filter);
        $this->assertEquals([], $report->getReportData());
    }
}

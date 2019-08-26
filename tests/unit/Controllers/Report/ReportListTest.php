<?php

use Controllers\Report\ReportList;
use PHPUnit\Framework\TestCase;

class ReportListTest extends TestCase
{
    private $reportFactory;
    private $reportRepository;
    private $authentication;

    public function testRequestReportList()
    {
        $request = new \GuzzleHttp\Psr7\ServerRequest('GET', '/api/reports');
        $response = new \Slim\Http\Response();

        $reportList = new ReportList($this->getMockContainer());
        $reportList($request, $response);
    }

    private function getMockContainer()
    {
        return new \Slim\Container([
            'report' => $this->getMockReportFactory()
        ]);
    }

    private function getMockReportFactory()
    {
        if (is_null($this->reportFactory)) {
            $this->reportFactory = $this
                ->getMockBuilder(\Services\Report\ServiceFactory::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();

            $this->reportFactory
                ->expects($this->once())
                ->method('getReportRepository')
                ->willReturn($this->getMockReportRepository());

            $this->reportFactory
                ->expects($this->once())
                ->method('getAuthenticatedUser')
                ->willReturn($this->getMockUser());
        }

        return $this->reportFactory;
    }

    private function getMockReportRepository()
    {
        if (is_null($this->reportRepository)) {
            $this->reportRepository = $this
                ->getMockBuilder(\Repositories\ReportRepository::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();

            $this->reportRepository
                ->expects($this->once())
                ->method('getReportList')
                ->with(
                    $this->isInstanceOf(\Services\Report\ReportFilterNormalizer::class),
                    $this->isType('integer'),
                    $this->isType('integer')
                )
                ->willReturn([]);
        }

        return $this->reportRepository;
    }

    private function getMockUser()
    {
        return new \Entities\User(['email_address' => 'test@alldigitalrewards.com']);
    }
}

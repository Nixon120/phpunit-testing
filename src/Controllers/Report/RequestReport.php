<?php
namespace Controllers\Report;

use Controllers\AbstractViewController;
use Entities\Base;
use Entities\Report;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\AbstractReport;
use Services\Report\EnrollmentFilterNormalizer;
use Services\Report\Interfaces\Reportable;
use Services\Report\ParticipantSummaryFilterNormalizer;
use Services\Report\PointBalanceFilterNormalizer;
use Services\Report\RedemptionFilterNormalizer;
use Services\Report\SweepstakeFilterNormalizer;
use Services\Report\ServiceFactory;
use Services\Report\TransactionFilterNormalizer;
use Slim\Http\Request;
use Slim\Http\Response;

class RequestReport
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Reportable
     */
    protected $reportService;

    /**
     * @var array
     */
    protected $errorContainer = [];

    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get('report');
    }

    public function __invoke(
        Request $request,
        Response $response
    ) {
    
        $this->request = $request;
        $this->response = $response;

        $this->requestReport();
        return $this->response;
    }

    private function requestReportFile(Reportable $report): bool
    {
        // Request report
        $entity = $report->request();
        // Publish report if request is generated
        if ($entity instanceof Base) {
            $this->response = $this->response->withStatus(200)
                ->withJson($entity->toArray());

            return true;
        }

        $this->response = $this->response->withStatus(400)
            ->withJson([]);

        return false;
    }

    private function requestPaginatedReport(Reportable $report): bool
    {
        $this->response = $this->response->withStatus(200)
            ->withJson(
                [
                    'reportName' => $report->getReportName(),
                    'reportData' => $report->getReportData(),
                    'reportHeaders' => $report->getReportHeaders()
                ]
            );
    }

    private function requestReport(): bool
    {
        $criteria = $this->request->getQueryParams() ?? [];
        $input = new InputNormalizer($criteria);

        try {
            $reportable = $this->getReportService($input);

            if ($input->getReportOutput() === 'file') {
                return $this->requestReportFile($reportable);
            }

            return $this->requestPaginatedReport($reportable);
        } catch (\Exception $e) {
            $this->response = $this->response
                ->withJson(
                    [
                        $e->getMessage()
                    ],
                    400
                );

            return false;
        }
    }

    private function getReportService(InputNormalizer $normalizer): Reportable
    {
        if (is_null($this->reportService)) {
            $report = new Report;
            $report->setReport($normalizer->getReportType());
            $reportClass = '\\Services\\Report\\' . $report->getReportClass();
            $reportFilter = '\\Services\\Report\\' . $report->getReportClass() . 'FilterNormalizer';
            /** @var Reportable $service */
            $service = new $reportClass($this->factory);
            $filter = new $reportFilter($normalizer->getInput());
            $service->setInputNormalizer($normalizer);
            $service->setFilter($filter);

            $service->setOffset($normalizer->getOffset());
            $service->setPage($normalizer->getPage());
            $service->setInputNormalizer($normalizer);

            $this->reportService = $service;
        }

        return $this->reportService;
    }
}

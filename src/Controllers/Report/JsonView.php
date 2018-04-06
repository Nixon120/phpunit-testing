<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 05/02/18
 * Time: 2:27 PM
 */

namespace Controllers\Report;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\EnrollmentFilterNormalizer;
use Services\Report\Interfaces\Reportable;
use Services\Report\PointBalanceFilterNormalizer;
use Services\Report\RedemptionFilterNormalizer;
use Services\Report\SweepstakeFilterNormalizer;
use Services\Report\ServiceFactory;

class JsonView extends AbstractViewController
{
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
    }

    public function reportData()
    {
        $criteria = $this->request->getQueryParams()??[];
        $input = new InputNormalizer($criteria);

        try {
            $report = $this->getReport($input);
            if ($input->getReportOutput() === 'file') {
                $report->export();
                $this->response = $this->response->withHeader(
                    'Content-Type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );
                return $this->response;
            }

            $response = $this->response->withStatus(200)
                ->withJson(
                    [
                        'reportName' => $report->getReportName(),
                        'reportData' => $report->getReportData(),
                        'reportHeaders' => $report->getReportHeaders()
                    ]
                );

            return $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function getReport(InputNormalizer $input): Reportable
    {
        switch ($input->getReportType()) {
            case 'enrollment':
                $report = $this->factory->getEnrollmentReport();
                $filter = new EnrollmentFilterNormalizer($input->getInput());
                $report->setFilter($filter);
                break;
            case 'redemption':
                $report = $this->factory->getRedemptionReport();
                $filter = new RedemptionFilterNormalizer($input->getInput());
                $report->setFilter($filter);
                break;
            case 'point_balance':
                $report = $this->factory->getPointBalanceReport();
                $filter = new PointBalanceFilterNormalizer($input->getInput());
                $report->setFilter($filter);
                break;
            case 'sweepstake':
                $report = $this->factory->getSweepstakeReport();
                $filter = new SweepstakeFilterNormalizer($input->getInput());
                $report->setFilter($filter);
                break;
            default:
                throw new \Exception('Report not found.');
                break;
        }

        $report->setOffset($input->getOffset());
        $report->setPage($input->getPage());
        $report->setInput($input);
        return $report;
    }
}

<?php

namespace Controllers\Report;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\EnrollmentFilterNormalizer;
use Services\Report\Interfaces\Reportable;
use Services\Report\PointBalanceFilterNormalizer;
use Services\Report\RedemptionFilterNormalizer;
use Services\Report\ServiceFactory;
use Services\Report\SweepstakeFilterNormalizer;
use Slim\Views\PhpRenderer;

class GuiView extends AbstractViewController
{
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderReport()
    {
        $criteria = $this->request->getQueryParams() ?? [];
        $input = new InputNormalizer($criteria);

        if (!empty($input->getInput()) && $input->isCriteriaBeingUpdated() !== true) {
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

                return $this->displayReport($report);
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        return $this->renderReportInterface($input);
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

    private function displayReport(Reportable $report)
    {
        $criteria = $this->request->getQueryParams();

        if (!empty($criteria['page'])) {
            unset($criteria['page']);
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'report/display.phtml',
                [
                    'currentReportCriteria' => http_build_query($criteria),
                    'report' => $report
                ]
            )
        );
    }

    private function renderReportInterface(InputNormalizer $input)
    {
        $organization = $program = null;
        if ($input->isCriteriaBeingUpdated()) {
            $organization = $this->factory->getOrganizationRepository()
                ->getOrganization(
                    $input->getOrganzationUuid(),
                    true
                );

            $program = $this->factory->getProgramRepository()
                ->getProgram($input->getProgramUuid());
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'report/interface.phtml',
                [
                    'criteria' => $input,
                    'enrollmentReport' => $this->factory->getEnrollmentReport(),
                    'redemptionReport' => $this->factory->getRedemptionReport(),
                    'balanceReport' => $this->factory->getPointBalanceReport(),
                    'sweepstakeReport' => $this->factory->getSweepstakeReport(),
                    'program' => $program,
                    'organization' => $organization
                ]
            )
        );
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 05/02/18
 * Time: 2:27 PM
 */

namespace Controllers\Report;

use Controllers\AbstractViewController;
use Entities\Base;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\EnrollmentFilterNormalizer;
use Services\Report\Interfaces\Reportable;
use Services\Report\ParticipantSummaryFilterNormalizer;
use Services\Report\PointBalanceFilterNormalizer;
use Services\Report\RedemptionFilterNormalizer;
use Services\Report\ReportFilterNormalizer;
use Services\Report\SweepstakeFilterNormalizer;
use Services\Report\ServiceFactory;
use Services\Report\TransactionFilterNormalizer;
use Slim\Http\Request;
use Slim\Http\Response;

class ReportList
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get('report');
    }

    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->request = $request;
        $this->response = $response;

        return $this->getReportList();
    }

    public function getReportList()
    {
        $get = $this->request->getQueryParams();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 30;
        $offset = $page === 1 ? 0 : ($page-1) * $limit;
        $filterNormalizer = new ReportFilterNormalizer($get);
        $collection = $this->factory->getReportRepository()
            ->getReportList($filterNormalizer, $offset, $limit);

        $response = $this->response->withStatus(200)
            ->withJson($collection);

        return $response;
    }
}

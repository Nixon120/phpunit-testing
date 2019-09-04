<?php

namespace Controllers\Report;

use Entities\User;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\ReportFilterNormalizer;
use Services\Report\ServiceFactory;
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

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get('report');
        $this->container = $container;
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
        $get['user'] = $this->factory->getAuthenticatedUser()->getEmailAddress();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 30;
        $offset = $page === 1 ? 0 : ($page - 1) * $limit;
        $filterNormalizer = new ReportFilterNormalizer($get);

        $collection = $this->factory->getReportRepository()
            ->getReportList($filterNormalizer, $offset, $limit);

        $response = $this->response->withStatus(200)
            ->withJson($collection);

        return $response;
    }
}

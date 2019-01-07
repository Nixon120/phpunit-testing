<?php

namespace Controllers\Sftp;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;

class SftpList
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
        $collection = $this->factory->getSftpRepository()
            ->getCollection();

        $response = $this->response->withStatus(200)
            ->withJson($collection);

        return $response;
    }
}

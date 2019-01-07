<?php

namespace Controllers\Sftp;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;

class SftpRetrieve
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
        $get = $this->request->getQueryParams();

        if (isset($get['id']) === true) {
            return $this->getSingle($get['id']);
        }

        return $this->getSftpList($get);
    }

    public function getSftpList($get)
    {
        $page = isset($get['page']) ? $get['page'] : 0;
        $offset = isset($get['offset']) ? $get['offset'] : 30;
        $collection = $this->factory->getSftpRepository()
            ->getCollection(null, $page, $offset);

        $response = $this->response->withStatus(200)
            ->withJson($collection);

        return $response;
    }

    public function getSingle($id)
    {
        $single = $this->factory->getSftpRepository()
            ->getSftpById($id);

        $response = $this->response->withStatus(200)
            ->withJson($single);

        return $response;
    }
}

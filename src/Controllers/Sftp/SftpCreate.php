<?php

namespace Controllers\Sftp;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Sftp\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;

class SftpCreate
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
        $this->factory = $container->get('sftp');
    }

    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->request = $request;
        $this->response = $response;

        return $this->create();
    }

    public function create()
    {
        $get = $this->request->getParsedBody();
        $get['user_id'] = $this->factory->getAuthenticatedUser()->getId();

        $saved = $this->factory->getSftpRepository()->insert($get);

        $response = $this->response->withStatus(200)
            ->withJson($saved);

        return $response;
    }
}

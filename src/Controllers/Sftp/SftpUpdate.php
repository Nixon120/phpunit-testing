<?php

namespace Controllers\Sftp;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Report\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;

class SftpUpdate
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
        ResponseInterface $response,
        array $args
    ) {
        $this->request = $request;
        $this->response = $response;
        return $this->update($args['id']);
    }

    public function update($id)
    {
        $get = $this->request->getParsedBody();
        $get['user_id'] = $this->factory->getAuthenticatedUser()->getId();

        $saved = $this->factory->getSftpRepository()
            ->update($id, $get);

        $response = $this->response->withStatus(200)
            ->withJson($saved);

        return $response;
    }
}

<?php

namespace Controllers\Sftp;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class SftpDelete
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
     * @var \Services\Sftp\ServiceFactory
     */
    private $factory;

    public function __construct(ContainerInterface $container)
    {
        $this->factory = $container->get('sftp');
    }

    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ) {
        $this->request = $request;
        $this->response = $response;
        return $this->delete($args['id']);
    }

    public function delete($id)
    {
        $single = $this->factory->getSftpRepository()
            ->getSftpById($id);

        if ($single->getUserId() != $this->factory->getAuthenticatedUser()->getId()) {
            return $this->response->withStatus(403);
        }

        $deleted = $this->factory->getSftpRepository()
            ->delete($id);

        $response = $this->response->withStatus(200)
            ->withJson($deleted);

        return $response;
    }
}

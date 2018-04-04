<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Authentication\Authenticate;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;
use Traits\RendererTrait;

abstract class AbstractViewController
{
    use RendererTrait;
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    protected $args;

    public function __construct(
        $request,
        $response,
        ?PhpRenderer $renderer = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->renderer = $renderer;
    }

    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function renderJsonInsufficientAccess()
    {
        $response = $this->response->withStatus(404)
            ->withJson(['message' => 'You lack the priviledges to continue']);

        return $response;
    }

    public function renderJson404()
    {
        $response = $this->response->withStatus(404)
            ->withJson(['message' => 'This resource does not exist']);

        return $response;
    }

    public function renderGuiInsufficientAccess()
    {
        return $this->render(
            $this->getRenderer()->fetch('insufficient-access.phtml', []),
            'empty.phtml'
        );
    }

    public function renderGui404()
    {
        $this->response = $this->response->withStatus(404);

        return $this->render(
            $this->getRenderer()->fetch('missing.phtml', []),
            'empty.phtml'
        );
    }
}

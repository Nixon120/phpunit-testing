<?php

namespace Services\Program\Sweepstake;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\Authentication\Authenticate;
use Services\Program\ServiceFactory;
use Slim\Http\Request;
use Slim\Http\Response;
use Validation\InputValidator;

class ValidationMiddleware
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Authenticate
     */
    private $auth;

    /**
     * @var InputValidator
     */
    private $validator;

    private $validationMessages = [];

    private $input = [];

    private $errors = [];

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->auth = $this->container->get('authentication');
        $this->validator = $this->container->get('validation');
    }

    /**
     * Kicks off token signing and authorization confirmation
     *
     * @param ServerRequestInterface $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     */
    public function __invoke(
        ServerRequestInterface $request,
        Response $response,
        callable $next = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $access = $this->request->getMethod() === 'GET' ? 'read' : 'write';
        $this->input = $this->request->getParsedBody() ?? [];

        if ($access === 'write' && $this->validate() === false) {
            //if GUI we can throw one way, if API we can throw structured way, or just make JS consume it as it's rendered
            //yeah.. makes sense.
            return $this->response = $this->response->withStatus(400)
                ->withHeader('Content-type', 'application/json')
                ->withJson([
                    'message' => _('Validation Failed'),
                    'errors' => [
                        'sku' => [
                            'INVALID' => 'Sku is not a valid product.'
                        ]
                    ]
                ]);
        }

        return $next($this->request, $this->response);
    }

    private function validate()
    {
        if (empty($this->input['sku'])) {
            return false;
        }

        $catalogService = $this->getServiceFactory()->getCatalogService();
        $product = $catalogService->getProduct($this->input['sku']);
        if ($product instanceof Product) {
            return true;
        }

        return false;
    }

    private function getServiceFactory()
    {
        return new ServiceFactory($this->container);
    }
}

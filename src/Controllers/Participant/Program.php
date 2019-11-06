<?php

namespace Controllers\Participant;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\ServiceFactory;

//@TODO remove? is this even in use?
class Program extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);

        $organizations = $this->factory
            ->getProgramService()
            ->get($input);

        $response = $this->response->withStatus(200)
            ->withJson($organizations);

        return $response;
    }
}

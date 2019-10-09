<?php

namespace Controllers\Program;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class GetProgramTypeJsonCollection
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        /** @var \Services\Program\ServiceFactory $serviceFactory */
        $serviceFactory = $this->getContainer()->get('program');
        $programTypeService = $serviceFactory->getProgramTypeService();
        $get = $request->getQueryParams();
        $input = new ProgramTypeInputNormalizer($get);
        $return = $programTypeService->get($input);
        $output = new ProgramTypeOutputNormalizer($return);
        $response = $response->withStatus(200)
            ->withJson($output->getList());

        return $response;
    }
}

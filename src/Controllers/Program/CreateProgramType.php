<?php

namespace Controllers\Program;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CreateProgramType
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
        try {
            /** @var \Services\Program\ServiceFactory $serviceFactory */
            $serviceFactory = $this->getContainer()->get('program');
            $programTypeService = $serviceFactory->getProgramTypeService();
            $post = $request->getParsedBody() ?? [];
            $input = new ProgramTypeInputNormalizer($post);
            if ($program = $programTypeService->insert($input)) {
                return $response = $response->withStatus(201);
            }

            $errors = $programTypeService->getErrors();
        } catch (\Exception $e) {
            $errors = [$e->getMessage()];
        }

        return $response = $response->withJson($errors, 400);
    }
}

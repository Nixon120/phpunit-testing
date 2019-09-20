<?php
namespace Controllers\Program;

use Services\Program\ServiceFactory;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class UpdateProgramType
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

    private function getProgramTypeRepository()
    {
        /** @var ServiceFactory $programServiceFactory */
        $programServiceFactory = $this->getContainer()->get('program');
        $repository = $programServiceFactory->getProgramTypeRepository();

        return $repository;
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        $typeId = $args['id'] ?? null;
        try {
            /** @var \Services\Program\ServiceFactory $serviceFactory */
            $serviceFactory = $this->getContainer()->get('program');
            $programTypeService = $serviceFactory->getProgramTypeService();
            $type = $this->getProgramTypeRepository()->getProgramType($typeId);
            if ($type === null) {
                throw new \Exception('Program type does not exist');
            }
            $post = $request->getParsedBody() ?? [];
            $input = new ProgramTypeInputNormalizer($post);

            if ($programTypeService->update($typeId, $input) === true) {
                return $response = $response->withStatus(204);
            }

            $errors = ['Unable to update program type'];
        } catch (\Exception $e) {
            $errors = [$e->getMessage()];
        }


        return $response = $response->withJson($errors, 400);
    }
}

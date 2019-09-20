<?php
namespace Controllers\Program;

use Services\Program\ServiceFactory;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class DeleteProgramType
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
        $typeId = $args['id'] ?? null;
        try {
            /** @var ServiceFactory $programServiceFactory */
            $programServiceFactory = $this->getContainer()->get('program');
            $repository = $programServiceFactory->getProgramTypeRepository();

            if ($repository->isProgramTypeInUse($typeId) === true) {
                throw new \Exception('Program type is currently in use');
            }

            if ($repository->deleteProgramType($typeId) === true) {
                return $response = $response->withStatus(204);
            }

            $errors = ['Unable to delete program type'];
        } catch (\Exception $e) {
            $errors = [$e->getMessage()];
        }


        return $response = $response->withJson($errors, 400);
    }
}

<?php

namespace Controllers\Program;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\Program;
use Services\Program\ServiceFactory;
use Slim\Views\PhpRenderer;

class Publish extends AbstractViewController
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

    public function updateProgramPublishSetting($programId, $publish)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);
        if (is_null($program)) {
            return $this->renderGui404();
        }

        if ($this->factory->getProgramRepository()->updatePublishColumn($programId, $publish) === true) {
            return $response = $this->response->withStatus(204);
        }

        return $response = $this->response->withStatus(400);
    }
}

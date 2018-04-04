<?php
namespace Controllers\Program;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\ServiceFactory;
use Slim\Views\PhpRenderer;

class Sweepstake extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderSweepstakeConfig($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderGui404();
        }

        $sweepstakeService = $this->factory->getSweepstakeService();

        if ($this->request->getParsedBody() !== null) {
            $success = $sweepstakeService->setConfiguration($program, $this->request->getParsedBody());
            return $response = $this->response->withStatus($success ? 200:400)
                ->withJson([]);
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'program/sweepstake.phtml',
                [
                    'program' => $program
                ]
            )
        );
    }
}

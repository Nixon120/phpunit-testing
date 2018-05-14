<?php
namespace Controllers\Program;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\ServiceFactory;

class SweepstakeJsonView extends AbstractViewController
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

    public function getSweepstakeConfig($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $response = $this->response->withStatus(404)
                ->withJson([]);
        }

        $sweepstakeService = $this->factory->getSweepstakeService();

        if ($this->request->getParsedBody() !== null) {
            $success = $sweepstakeService->setConfiguration(
                $program,
                $this->request->getParsedBody()
            );
            return $response = $this->response->withStatus($success ? 200:400)
                ->withJson([]);
        }

        $sweepstake = $program->getSweepstake();
        $output = new SweepstakeOutputNormalizer($sweepstake);
        $response = $this->response->withStatus(200)
            ->withJson($output->get());
        return $response ;
    }
}

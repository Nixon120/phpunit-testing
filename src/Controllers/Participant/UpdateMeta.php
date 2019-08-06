<?php

namespace Controllers\Participant;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\Participant;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class UpdateMeta
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Participant
     */
    private $service;

    public function __construct(Container $container)
    {
        $this->service = $container->get('participant')
            ->getService();
    }

    public function __invoke(Request $request, Response $response, array $args)
    {
        $this->request = $request;
        $this->response = $response;

        $participantId = $args['id'] ?? null;
        $participant = $this->service->getSingle($participantId);

        if ($participant !== null) {

            $data = $this->request->getParsedBody() ?? [];

            try {
                if ($this->service->updateMeta($participant, $data) !== true) {
                    return $this->returnJson(400, $this->service->repository->getErrors());
                }

                return $this->returnJson(200);
            } catch (\Exception $e) {
                return $this->returnJson(400, [$e->getMessage()]);
            }
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    private function returnJson($statusCode, $return = null)
    {
        $response = $this->response->withStatus($statusCode);

        if ($return !== null) {
            $response->withJson($return);
        }
        return $response;
    }
}

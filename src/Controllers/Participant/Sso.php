<?php
namespace Controllers\Participant;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\ServiceFactory;
use \Services\Participant\Participant;
use Slim\Http\Request;
use Slim\Http\Response;

class Sso
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Participant
     */
    private $service;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->service = $factory->getService();
    }

    public function authenticateSsoToken($programId, $uniqueId)
    {
        $get = $this->request->getQueryParams();
        if ($participant = $this->service->authenticateSso($programId, $uniqueId, $get['token'])) {
            $output = new OutputNormalizer($participant);
            //@TODO: implement output normalization
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            return $this->returnJson(200, $output->get());
        }
        return $this->returnJson(400, ['No token match']);
    }

    public function generateSso($programId, $uniqueId)
    {
        $participantToken = $this->service->generateSso($programId, $uniqueId);

        if (!isset($participantToken['error'])) {
            //@TODO: implement output normalization
            //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
            return $this->returnJson(201, $participantToken);
        }
        return $this->returnJson(400, [$participantToken['message']]);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}

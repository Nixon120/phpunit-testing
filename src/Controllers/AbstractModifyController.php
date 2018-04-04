<?php
namespace Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

abstract class AbstractModifyController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }

    public function returnFormattedJsonError($statusCode, $return = [])
    {
        $errors = [
            'message' => _('Validation failed'),
            'errors' => $return
        ];

        return $this->response->withStatus($statusCode)
            ->withJson($errors);
    }
}

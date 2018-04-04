<?php
namespace Controllers\Authentication;

use Services\Authentication\Authenticate;
use Slim\Http\Request;
use Slim\Http\Response;

class ApiLogin
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $args;

    /**
     * @var Authenticate
     */
    private $auth;

    private $roles;

    private $authRoutes;

    public function __construct(
        Authenticate $auth,
        array $roles,
        array $routes
    ) {
        $this->auth = $auth;
        $this->roles = $roles;
        $this->authRoutes = $routes;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        $this->auth->setResponse($this->response);
        $this->auth->setRequest($this->request);

        $post = $this->request->getParsedBody();

        //@TODO validation will solve if post isn't set
        if ($post && $this->processLogin() === true) {
            return $this->response->withStatus(200)
                ->withJson($this->getAuthenticatedResponsePayload());
        }

        return $this->response->withStatus(403)
            ->withJson([
                'message' => 'Authentication failed',
                'errors' => [_("We were unable to authenticate user")]
            ]);
        //ensure middleware is developed to validate
    }

    private function getAuthenticatedResponsePayload()
    {
        $scope = $this->roles[$this->auth->getUser()->getRole()];
        $this->auth->getToken()->setRequestedScopes($scope);
        $token = $this->auth->getToken()->generateUserToken($this->auth->getUser());

        return [
            'message' => 'Authentication succeeded',
            'token' => $token['token'],
            'redirect' => $this->authRoutes[$this->auth->getUser()->getRole()],
            'userInfo' => $this->auth->getUser()
        ];
    }

    private function processLogin()
    {
        if (!$this->auth->validate()) {
            return false;
        }

        return true;
    }
}

<?php

namespace Controllers\Authentication;

use Services\Authentication\Authenticate;
use Services\Authentication\AuthAttemptValidation;
use Slim\Http\Request;
use Slim\Http\Response;
use Services\CacheService;

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

    protected $cacheService;

    public function __construct(
        Authenticate $auth,
        array $roles,
        array $routes,
        CacheService $cacheService
    ) {
        $this->auth = $auth;
        $this->roles = $roles;
        $this->authRoutes = $routes;
        $this->cacheService = $cacheService;
    }

    public function __invoke(Request $request, Response $response, $args)
    {

        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        $this->auth->setResponse($this->response);
        $this->auth->setRequest($this->request);

        $post = $this->request->getParsedBody();
        $authAttemptValidation = new AuthAttemptValidation($this->cacheService);
        $validation_blocked = $authAttemptValidation($this->request, $this->response);
        if ($validation_blocked) {
            return $validation_blocked;
        }

        //@TODO validation will solve if post isn't set
        if ($post && $this->processLogin() === true) {
            return $this->response->withStatus(200)
                ->withJson($this->getAuthenticatedResponsePayload());
        }


        $this->cacheInvalidAttempt();
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

    protected function cacheInvalidAttempt(): void
    {
        $attempts = 0;
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $key = 'LOCKOUT_' . $ip;

        if ($this->cacheService->cachedItemExists($key) === true) {
            $attempts = $this->cacheService->getCachedItem($key);
        }

        $attempts++;

        $this->cacheService->cacheItem($attempts, $key);
    }
}

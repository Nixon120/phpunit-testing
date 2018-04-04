<?php

namespace Services\Authentication;

use Dflydev\FigCookies\FigResponseCookies;
use Entities\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;
use Traits\LoggerAwareTrait;

class Authenticate
{
    use LoggerAwareTrait;

    //@TODO: I don't like not having typehinting between these classes, because of slims additional features
    //in the request/response objects, but things like getUri seem like they should be standard.
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Messages
     */
    private $flash;

    /**
     * @var \PDO
     */
    private $database;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $authRedirectUrl;

    private $programIdContainer;

    private $organizationIdContainer;

    public function __construct(\PDO $database, Token $token)
    {
        $this->database = $database;
        $this->token = $token;
    }

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function setAuthRedirectUrl($url)
    {
        $this->authRedirectUrl = $url;
    }

    public function getAuthRedirectUrl()
    {
        return $this->authRedirectUrl;
    }

    public function setFlashMessaging(Messages $flash)
    {
        $this->flash = $flash;
    }

    public function hasAccess($system, $action)
    {
        if ($system === '') {
            //allow dashboard.
            return true;
        }
        $scope = $this->getToken()->getRequestedScopes();//->decoded->scope;
        $request = implode('.', [$system, $action]);
        $wildcard = implode('.', [$system, 'all']);

        if (!in_array($request, $scope) && !in_array($wildcard, $scope)) {
            return false;
        }

        return true;
    }

    public function isLogged()
    {
        if ($this->getUser() !== null) {
            return true;
        }

        return false;
    }

    public function isRequestOriginAPI(): bool
    {
        $path = $this->request->getUri()->getPath();
        $whitelisted = '/api';
        if (strpos($path, $whitelisted) !== false || strpos($path, '/token') !== false) {
            return true;
        }

        return false;
    }

    public function isWhitelisted(): bool
    {
        $path = $this->request->getUri()->getPath();
        $whitelisted = ['login', 'logout', 'user/login', 'administrators/recovery', 'api/administrators/recovery', 'token', 'invite', 'healthz'];

        if ($path === "/") {
            return true;
        }

        foreach ($whitelisted as $listed) {
            if (strstr($path, $listed) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isValidated()
    {
        $whitelisted = $this->isWhitelisted();
        if ($whitelisted === false && !$this->getToken()->isTokenValid()) {
            return false;
        }

        return true;
    }

    public function isApiRequest(): bool
    {
        $activeRoute = ltrim($this->request->getUri()->getPath(), '/');
        $fragments = explode('/', $activeRoute);

        if ($fragments[0] === 'api') {
            return true;
        }

        return false;
    }

    public function isAuthorized()
    {
        $activeRoute = ltrim($this->request->getUri()->getPath(), '/');
        $fragments = explode('/', $activeRoute);

        if ($fragments[0] === 'api') {
            array_shift($fragments);
            $fragments = array_values($fragments);
        }

        $system = $fragments[0];
        $access = $this->request->getMethod() === 'GET' ? 'read' : 'write';

        $whitelist = $this->isWhitelisted();
        $access = $this->hasAccess($system, $access);
        if ($whitelist === false && $access === false) {
            return false;
        };

        return true;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function validate()
    {
        $post = $this->request->getParsedBody();

        $sql = "SELECT * FROM User WHERE email_address = ? AND active = 1";
        $args = [
            $post['email_address'],
        ];
        /** @var ?User $user */
        $user = $this->query($sql, $args, User::class);

        if ($user && password_verify($post['password'], $user->getPassword())) {
            $this->user = $user;
            $this->getLogger()->notice(
                'Login Success',
                [
                    'subsystem' => 'authentication',
                    'email' => $post['email_address'],
                    'action' => 'login',
                    'success' => true
                ]
            );
            return true;
        }

        $this->getLogger()->notice(
            'Login Failure',
            [
                'subsystem' => 'authentication',
                'email' => $post['email_address'],
                'action' => 'login',
                'success' => false
            ]
        );

        return false;
    }

    public function unableToAuthenticate()
    {
        $this->flash->addMessage('warning', 'Sorry, invalid email and/or password.');
        return $this->response = $this->response->withRedirect('/login', 303);
    }

    public function establishUserIsAuthenticated($scope)
    {
        //@TODO: throw exception on failure.
        $this->getToken()->setRequestedScopes($scope);
        $data = $this->getToken()->generateUserToken($this->getUser());

        $this->response = FigResponseCookies::set($this->response, \Dflydev\FigCookies\SetCookie::create('token')->withValue($data['token']));
        $this->response = FigResponseCookies::set($this->response, \Dflydev\FigCookies\SetCookie::create('token_expires')->withValue($data['expires']));

        return $this->response = $this->response->withRedirect($this->getAuthRedirectUrl(), 200);
    }

    public function establishApiIsAuthenticated($scope)
    {
        $this->getToken()->setRequestedScopes($scope);
        //Need to pull email from server params
        $server = $this->request->getServerParams();
        $email = $server["PHP_AUTH_USER"];
        $this->getUserFromDatabase($email);
        $data = $this->getToken()->generateUserToken($this->getUser());
        //@TODO change to $this->>response()->withJson
        return $this->response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function getUser():?User
    {
        if (is_null($this->user) && !is_null($this->getToken()->decoded)) {
            $this->getUserFromDatabase($this->getToken()->decoded->sub);
        }

        return $this->user;
    }

    private function getUserFromDatabase($email)
    {
        $sql = "SELECT * FROM User WHERE email_address = ?";
        $args = [
            $email,
        ];


        if (!$user = $this->query($sql, $args, User::class)) {
            return null;
        }
        /** @var User $user */
        if ($user->getOrganizationId() !== null) {
            $user->setOrganizationOwnershipIdentificationCollection(
                $this->getUserOrganizationIdContainer($user->getOrganizationId())
            );

            if(!empty($user->getOrganizationOwnershipIdentificationCollection())) {
                $user->setProgramOwnershipIdentificationCollection(
                    $this->getUserProgramIdContainer($user->getOrganizationOwnershipIdentificationCollection())
                );
            }
        }
        /** @var User $user */
        $this->user = $user;
    }

    private function query($sql, $args, $class)
    {
        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $class);
        return $sth->fetch();
    }

    private function getUserProgramIdContainer(array $organizationIdContainer): array
    {
        $programs = $this->getProgramTreeIdContainer($organizationIdContainer);
        return $this->formatResultIdRows($programs);
    }

    private function getUserOrganizationIdContainer(int $organizationId): array
    {
        $organizations = $this->getOrganizationTreeIdContainer($organizationId);
        return $this->formatResultIdRows($organizations);
    }

    private function getProgramTreeIdContainer(array $organizationIdContainer)
    {
        $organizationIdString = implode(',', $organizationIdContainer);

        $sql = <<<SQL
SELECT Program.id, Program.unique_id FROM Program 
JOIN Organization ON Program.organization_id = Organization.id
WHERE organization_id IN ({$organizationIdString})
SQL;
        $sth = $this->database->query($sql);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getOrganizationTreeIdContainer(int $organizationId)
    {
        $sql = <<<SQL
SELECT b.id, b.unique_id
FROM Organization a
  JOIN Organization as b ON (b.lft >= a.lft and b.rgt <= a.rgt and b.lvl >= a.lvl)
WHERE a.id = ?
SQL;

        $args = [
            $organizationId
        ];

        $sth = $this->database->prepare($sql);
        $sth->execute($args);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function formatResultIdRows(array $rows)
    {
        $container = [];

        if (!empty($rows)) {
            foreach ($rows as $key => $row) {
                $container[$row['unique_id']] = $row['id'];
            }
        }

        return $container;
    }
}

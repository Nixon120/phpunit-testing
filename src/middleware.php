<?php

use \Psr\Container\ContainerInterface;

// So what we've done here is, if we're instantiating a new object that will be used in $app->add($object),
// I just put that method call here, similar to a dependency. If it's not being placed in $app->add
// place it in dependency.php

// If we're doing more than instantiating the object, such as, $obj = new Object; $obj->authorized();
// We create a class/service for this process, and use the alternative $this->add syntax.
// Those middleware should work with __invoke, see "Service\Authentication\Authorize" as an example.

$container["HttpBasicAuthentication"] = function (ContainerInterface $container) {
    /** @var \Services\User\UserRead $user */
    $user = $container->get('user')->getUserRead();
    $userAuthContainer = $user->getApiAuthUserContainer();

    return new \Slim\Middleware\HttpBasicAuthentication([
        "path" => "/token",
        "relaxed" => ["localhost"],
        "secure" => false,
        "error" => function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
            //@TODO handle properly with message
            return $response = $response->withJson(
                [
                    'message' => $args['message']
                ],
                401
            );
        },
        "users" => $userAuthContainer
    ]);
};

$container["JwtAuthentication"] = function (ContainerInterface $container) use ($app) {
    $pathExemptions = new \Slim\Middleware\JwtAuthentication\RequestPathRule([
        "passthrough" => ["/login", "/logout", "/user/login", "/administrators/recovery", "/api/administrators/recovery", "/ping", "/token", "/healthz", "/invite"]
    ]);

    return new \Slim\Middleware\JwtAuthentication([
        "path" => "/",
        "secure" => false,
        "rules" => [$pathExemptions],
        "secret" => getenv("JWT_SECRET"),
        "logger" => $container["logger"],
        "attribute" => false,
        "relaxed" => ["localhost"],
        "error" => function (\Slim\Http\Request $request, \Slim\Http\Response $response, $arguments) use ($container) {
            /** @var \Slim\Http\Response $response */

            /** @var \Services\Authentication\Authenticate $auth */
            $auth = $container->get('authentication');
            $auth->setRequest($request);
            $auth->setResponse($response);
            if ($auth->isApiRequest()) {
                $data["status"] = "error";
                $data["message"] = $arguments["message"];
                return $response->withJson($data, 401);
            } else {
                // There is a bug in the JwtAuthentication library, which forces all responses status codes to be 401 for
                // this callback. This was fixed in 3.0.0 RC 5, but there are some other changes we need to make
                // This should be revisited to handle the error via package, rather then utilizing php's header method
                // directly.
                // https://github.com/tuupola/slim-jwt-auth/issues/84
                // Changes from 2.3 to 3.0 https://github.com/tuupola/slim-jwt-auth/blob/3.x/CHANGELOG.md
                header('Status: 302 Found', false, 302);
                header('Location: /login');
            }
            exit;
        },
        "callback" => function (\Slim\Http\Request $request, \Psr\Http\Message\ResponseInterface $response, $arguments) use ($container) {
            /** @var \Services\Authentication\Authenticate $authentication */
            //@TODO elegance?
            $authentication = $container['authentication'];

            $cookies = Dflydev\FigCookies\Cookies::fromRequest($request);
            if ($cookies->get('token') !== null) {
                // It's an user request
                $authBearerTokenString = $cookies->get('token')->getValue();
                $authentication->getToken()->setToken($authBearerTokenString);
            } else {
                // It's an API request
                $authBearerTokenString = explode(' ', $request->getHeaderLine('HTTP_AUTHORIZATION'))[1];
                $authentication->getToken()->setToken($authBearerTokenString);
            }

            $authRoutes = $container['defaultRoutes'];
            $roles = $container->get('roles');
            $authentication->getToken()->hydrate($arguments["decoded"]);
            $authentication->getUser();
            if ($authentication->getToken()->decoded->sub !== 'rewardstack@alldigitalrewards.com') {
                $scope = $roles[$authentication->getUser()->getRole()];
                $authentication->getToken()->setRequestedScopes($scope);
                $redirect = $authRoutes[$authentication->getUser()->getRole()];
                $authentication->setAuthRedirectUrl($redirect);
            } else {
                $authentication->getToken()->setRequestedScopes($roles['superadmin']);
            }
        }
    ]);
};

$container["Cors"] = function ($container) {
    return new \Tuupola\Middleware\Cors([
        "logger" => $container["logger"],
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => [
            "Authorization",
            "If-Match",
            "If-Unmodified-Since",
            "content-type"
        ],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $args) {
            throw new \Exception($args['message']);
        }
    ]);
};

$container["Negotiation"] = function ($container) {
    return new \Gofabian\Negotiation\NegotiationMiddleware([
        "accept" => ["application/json"]
    ]);
};

$app->add(new \Middleware\UserPermissionValidator($container->get('authentication')));
$app->add("HttpBasicAuthentication");
$app->add("JwtAuthentication");
$app->add("Cors");
$app->add("Negotiation");

if(getenv('LOG_API_REQUEST_BODIES') == 1) {
    $app->add(\Middleware\LogApiRequestBodiesMiddleware::class);
}
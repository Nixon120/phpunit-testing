<?php
//@TODO clean up
$app->post("/token", function ($request, $response, $args) use ($app) {
    $container = $app->getContainer();
    /** @var \Services\Authentication\Authenticate $authentication */
    $authentication = $container->get('authentication');
    $roles = $container->get('roles');

    // Now compare this to the request body
    return $authentication->establishApiIsAuthenticated($roles);
});

$app->get("/healthz", function ($request, $response) {
    return $response;
});

/* This is just for debugging, not usefull in real life. */
$app->get("/dump", function ($request, $response, $arguments) {
    print_r($this->token);
});

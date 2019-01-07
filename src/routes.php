<?php

// Include routes

$app->get('/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    /** @var \Services\Authentication\Authenticate $authentication */
    $authentication = $this->get('authentication');
    $redirect = $authentication->getAuthRedirectUrl();
    return $response = $response->withRedirect($redirect);
});

require __DIR__ . '/Routes/authentication.php';
require __DIR__ . '/Routes/user.php';
require __DIR__ . '/Routes/participant.php';
require __DIR__ . '/Routes/product.php';
require __DIR__ . '/Routes/organization.php';
require __DIR__ . '/Routes/program.php';
require __DIR__ . '/Routes/report.php';
require __DIR__ . '/Routes/sftp.php';

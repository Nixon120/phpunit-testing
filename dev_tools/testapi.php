<?php

require __DIR__ . '/../vendor/autoload.php';
session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$container = new \Slim\Container($settings);

$app = new \Slim\App($container);

require __DIR__ . '/../src/dependencies.php';

$app->map(
    ['GET', 'POST'],
    "/",
    function (
        \Psr\Http\Message\RequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        /**
         * @var \Psr\Log\LoggerInterface $logger
         */
        $logger = $this->get('logger');
        $logger->info($request->getBody());
    }
);

$app->run();

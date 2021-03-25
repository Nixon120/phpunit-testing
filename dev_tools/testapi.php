<?php

use Factories\LoggerFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Container;

require __DIR__ . '/../vendor/autoload.php';
session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$container = new Container($settings);

$app = new App($container);

require __DIR__ . '/../src/dependencies.php';

$app->map(
    ['GET', 'POST'],
    "/",
    function (
        RequestInterface $request,
        ResponseInterface $response
    ) {
        /**
         * @var LoggerInterface $logger
         */
        $logger = LoggerFactory::getInstance();
        $logger->info($request->getBody());
    }
);

$app->run();

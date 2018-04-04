<?php
/**
 * Basic bootstrap for CLI scripts.
 */
require_once "vendor/autoload.php";

define('ROOT', __DIR__);

use Slim\Container;

$settings = require __DIR__ . '/src/settings.php';

// Create our Container of Righteousness.
$container = new Container($settings);

// Include operational dependencies (logger, renderer, etc)
require __DIR__ . '/src/dependencies.php';


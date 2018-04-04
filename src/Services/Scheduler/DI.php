<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['scheduler'] = function (ContainerInterface $c) {
    return new \Services\Scheduler\ServiceFactory($c);
};
//@TODO: set controller containers with ::class & __invoke to constructor load

<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['program'] = function (ContainerInterface $c) {
    return new \Services\Program\ServiceFactory($c);
};

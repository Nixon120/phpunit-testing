<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['program'] = function (ContainerInterface $c) {
    return new \Services\Program\ServiceFactory($c);
};

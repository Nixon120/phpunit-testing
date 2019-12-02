<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['organization'] = function (ContainerInterface $c) {
    return new \Services\Organization\ServiceFactory($c);
};

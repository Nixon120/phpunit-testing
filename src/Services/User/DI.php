<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['user'] = function (ContainerInterface $c) {
    return new Services\User\ServiceFactory($c);
};

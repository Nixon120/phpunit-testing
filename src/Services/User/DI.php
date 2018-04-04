<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['user'] = function (ContainerInterface $c) {
    return new Services\User\ServiceFactory($c);
};

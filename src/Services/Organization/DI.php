<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['organization'] = function (ContainerInterface $c) {
    return new \Services\Organization\ServiceFactory($c);
};

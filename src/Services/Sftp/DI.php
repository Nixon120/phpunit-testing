<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['report'] = function (ContainerInterface $c) {
    return new \Services\Report\ServiceFactory($c);
};

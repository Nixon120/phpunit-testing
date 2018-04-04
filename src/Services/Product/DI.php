<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['product'] = function (ContainerInterface $c) {
    return new \Services\Product\ServiceFactory($c);
};

<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['product'] = function (ContainerInterface $c) {
    return new \Services\Product\ServiceFactory($c);
};

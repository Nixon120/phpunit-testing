<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['participant'] = function (ContainerInterface $c) {
    return new Services\Participant\ServiceFactory($c);
};

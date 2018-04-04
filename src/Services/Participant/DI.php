<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['participant'] = function (ContainerInterface $c) {
    return new Services\Participant\ServiceFactory($c);
};

<?php
// DIC configuration
use Psr\Container\ContainerInterface;

$container['sftp'] = function (ContainerInterface $c) {
    return new \Services\Sftp\ServiceFactory($c);
};

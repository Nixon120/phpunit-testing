<?php
// DIC configuration
use Interop\Container\ContainerInterface;

$container['sftp'] = function (ContainerInterface $c) {
    return new \Services\Sftp\ServiceFactory($c);
};

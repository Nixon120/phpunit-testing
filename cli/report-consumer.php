#!/usr/bin/env php
<?php
/**
 * @var \Psr\Container\ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";
$amqpStreamConnectionFactory = new \AllDigitalRewards\AMQP\AMQPStreamConnectionFactory($container);

$amqpConfig = $container->get('amqpConfig');
$channelConfig = $amqpConfig['channels']['reports'];

$consumer = new \AllDigitalRewards\AMQP\Consumer(
    $amqpStreamConnectionFactory(),
    $channelConfig['channelName'],
    $channelConfig['taskRunner'],
    $channelConfig['maxConsumers'],
    $channelConfig['maxConsumerRuntime']
);

$consumer->getChannel()->basic_qos(0, 50, false);

$consumer->run();
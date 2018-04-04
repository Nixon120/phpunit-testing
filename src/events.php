<?php
/**
 * @var \Psr\Container\ContainerInterface $container
 * @var \League\Event\Emitter $emitter
 */

$emitter = new League\Event\Emitter();
$emitter->useListenerProvider(new \Events\Providers\OrganizationRaProvider($container));
$emitter->useListenerProvider(new \Events\Providers\ProgramRaProvider($container));
$emitter->useListenerProvider(new \Events\Providers\AdjustmentProvider($container));
$emitter->useListenerProvider(new \Events\Providers\TransactionWebhookProvider($container));

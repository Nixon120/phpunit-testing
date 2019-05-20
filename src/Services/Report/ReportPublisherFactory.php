<?php
namespace Services\Report;

use AllDigitalRewards\AMQP\AMQPStreamConnectionFactory;
use AllDigitalRewards\AMQP\MessagePublisher;
use Psr\Container\ContainerInterface;

class ReportPublisherFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(): MessagePublisher
    {
        $amqpStreamConnectionFactory = new AMQPStreamConnectionFactory($this->container);
        $amqpStreamConnection = $amqpStreamConnectionFactory();

        $amqpConfig = $this->container->get('amqpConfig');

        return new MessagePublisher(
            $amqpStreamConnection,
            $amqpConfig['channels']['reports']['channelName']
        );
    }
}

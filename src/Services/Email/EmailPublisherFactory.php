<?php

namespace Services\Email;

use AllDigitalRewards\AMQP\AMQPStreamConnectionFactory;
use AllDigitalRewards\AMQP\MessagePublisher;
use Psr\Container\ContainerInterface;

class EmailPublisherFactory
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

        return new MessagePublisher(
            $amqpStreamConnection,
            'EmailSend'
        );
    }
}

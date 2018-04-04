<?php
namespace Events\Providers;

use AllDigitalRewards\AMQP\AMQPStreamConnectionFactory;
use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\RAP\Client;
use Events\EventPublisherFactory;
use League\Event\Emitter;
use League\Event\ListenerProviderInterface;

abstract class AbstractProvider implements ListenerProviderInterface
{
    private $container;

    private $emitter;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->container = $container;
        $this->emitter = new Emitter;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEmitter()
    {
        return $this->emitter;
    }

    protected function getMessagePublisher(): MessagePublisher
    {
        $amqpConfig = $this->container->get('amqpConfig');
        $channelConfig = $amqpConfig['channels']['events'];
        $amqpStreamConnectionFactory = new AMQPStreamConnectionFactory($this->container);
        $amqpStreamConnection = $amqpStreamConnectionFactory();

        return new MessagePublisher(
            $amqpStreamConnection,
            $channelConfig['channelName']
        );
    }

    protected function getRaClient(): Client
    {
        $raCredentials = $this->getContainer()->get('settings')['raCredentials'];
        $httpClient = new \GuzzleHttp\Client([
            'base_uri' => $raCredentials['endpoint'],
            'http_errors' => false,
            'allow_redirects' => false
        ]);

        # Connect
        $client = new Client(
            $raCredentials['username'],
            $raCredentials['password'],
            $httpClient
        );

        return $client;
    }
}

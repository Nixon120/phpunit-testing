<?php

namespace Events\Listeners;

use AllDigitalRewards\AMQP\MessagePublisher;
use Entities\Event;
use League\Event\ListenerInterface;

abstract class AbstractListener implements ListenerInterface
{
    /**
     * @var MessagePublisher
     */
    private $publisher;

    /**
     * @var string
     */
    private $error;

    public function __construct(MessagePublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function isListener($listener)
    {
        return $listener === $this;
    }

    protected function getError(): ?string
    {
        return $this->error;
    }

    protected function setError(string $error)
    {
        $this->error = $error;
    }

    protected function reQueueEvent(Event $event)
    {
        $event->incrementAttemptCount();
        $event->setError($this->getError());

        //It's failed, so we're going to requeue the event with just this action.
        $reQueueEvent = $event->toArray();
        unset($reQueueEvent['propagationStopped'], $reQueueEvent['emitter']);

        $message = json_encode($reQueueEvent);
        $this->publisher->publish($message);
    }
}

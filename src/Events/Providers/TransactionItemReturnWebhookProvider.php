<?php

namespace Events\Providers;

use Events\Listeners\Transaction\TransactionItemReturnWebhookListener;
use League\Event\ListenerAcceptorInterface;
use Repositories\WebhookRepository;
use Services\Participant\ServiceFactory;

class TransactionItemReturnWebhookProvider extends AbstractProvider
{
    /**
     * @var ListenerAcceptorInterface
     */
    private $acceptor;

    /**
     * @var ServiceFactory
     */
    private $participantServiceFactory;

    public function provideListeners(ListenerAcceptorInterface $acceptor)
    {
        $this->acceptor = $acceptor;
        $this->participantServiceFactory = $this->getContainer()->get('participant');
        $this->addListener();
    }

    private function addListener()
    {
        $events = ['TransactionItemReturnWebhook.create'];
        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getTransactionItemReturnWebhookListener()
            );
        }, $events);
    }

    private function getTransactionItemReturnWebhookListener()
    {
        return new TransactionItemReturnWebhookListener(
            $this->getMessagePublisher(),
            $this->participantServiceFactory,
            $this->getWebhookRepository()
        );
    }

    /**
     * @return WebhookRepository
     */
    private function getWebhookRepository()
    {
        return new WebhookRepository($this->getContainer()->get('database'));
    }
}

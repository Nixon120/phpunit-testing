<?php

namespace Events\Providers;

use Events\Listeners\Transaction\TransactionItemRefundWebhookListener;
use League\Event\ListenerAcceptorInterface;
use Repositories\WebhookRepository;
use Services\Participant\ServiceFactory;

class TransactionItemRefundWebhookProvider extends AbstractProvider
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
        $events = ['TransactionItemRefundWebhook.create'];
        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getTransactionItemRefundWebhookListener()
            );
        }, $events);
    }

    private function getTransactionItemRefundWebhookListener()
    {
        return new TransactionItemRefundWebhookListener(
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

<?php

namespace Events\Providers;

use Events\Listeners\Transaction\TransactionWebhookListener;
use League\Event\ListenerAcceptorInterface;
use Repositories\WebhookRepository;
use Services\Participant\TransactionServiceFactory;
use Services\Participant\Participant;

class TransactionWebhookProvider extends AbstractProvider
{
    /**
     * @var ListenerAcceptorInterface
     */
    private $acceptor;

    public function provideListeners(ListenerAcceptorInterface $acceptor)
    {
        $this->acceptor = $acceptor;

        $this->addListeners();
    }

    private function addListeners()
    {
        $transactionCreateEvents = [
            'Transaction.create',
            'Transaction.create.webhook.*'
        ];

        array_walk($transactionCreateEvents, function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                new TransactionWebhookListener(
                    $this->getMessagePublisher(),
                    $this->getTransactionService(),
                    $this->getParticipantService(),
                    $this->getWebhookRepository()
                )
            );
        });
    }

    /**
     * @return Participant
     */
    private function getParticipantService()
    {
        $userServiceFactory = $this->getContainer()->get('participant');
        return $userServiceFactory->getService();
    }

    /**
     * @return \Services\Participant\Transaction
     */
    private function getTransactionService()
    {
        $transactionServiceFactory = new TransactionServiceFactory($this->getContainer());

        return $transactionServiceFactory();
    }

    /**
     * @return \Repositories\WebhookRepository
     */
    private function getWebhookRepository()
    {
        return new WebhookRepository($this->getContainer()->get('database'));
    }
}

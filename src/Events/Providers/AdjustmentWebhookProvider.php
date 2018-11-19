<?php

namespace Events\Providers;

use Events\Listeners\Adjustment\AdjustmentWebhookListener;
use League\Event\ListenerAcceptorInterface;
use Repositories\WebhookRepository;
use Services\Participant\ServiceFactory;

class AdjustmentWebhookProvider extends AbstractProvider
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

        $this->addParticipantPointAdjustmentListeners();
    }

    private function addParticipantPointAdjustmentListeners()
    {
        $events = ['AdjustmentWebhook.credit', 'AdjustmentWebhook.debit'];
        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getAdjustmentWebhookListener()
            );
        }, $events);
    }

    private function getAdjustmentWebhookListener()
    {
        return new AdjustmentWebhookListener(
            $this->getMessagePublisher(),
            $this->participantServiceFactory->getService(),
            $this->participantServiceFactory->getBalanceService(),
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

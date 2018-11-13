<?php

namespace Events\Providers;

use Events\Listeners\Adjustment\AutoRedemption;
use Events\Listeners\Adjustment\SweepstakeEntry;
use Events\Listeners\Transaction\AdjustmentWebhookListener;
use League\Event\ListenerAcceptorInterface;
use Repositories\WebhookRepository;
use Services\Participant\ServiceFactory;

class AdjustmentProvider extends AbstractProvider
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

        $this->addAutoRedemptionListeners();
        $this->addSweepstakeRedemptionListeners();
        $this->addParticipantPointAdjustmentListeners();
    }

    private function addAutoRedemptionListeners()
    {
        $events = ['Adjustment.credit', 'Adjustment.autoRedemption'];

        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getAutoRedemption()
            );
        }, $events);
    }

    private function addSweepstakeRedemptionListeners()
    {
        $events = ['Adjustment.credit', 'Adjustment.sweepstakeEntry'];

        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getSweepstakeEntry()
            );
        }, $events);
    }

    private function addParticipantPointAdjustmentListeners()
    {
        $events = ['Adjustment.credit', 'Adjustment.debit', 'Adjustment.create.webhook.*'];
        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                $this->getAdjustmentWebhookListener()
            );
        }, $events);
    }

    private function getAutoRedemption()
    {
        return new AutoRedemption(
            $this->getMessagePublisher(),
            $this->participantServiceFactory->getService(),
            $this->participantServiceFactory->getTransactionService()
        );
    }

    private function getSweepstakeEntry()
    {
        /**
         * @var \Services\Program\ServiceFactory $programService
         */
        $programService = $this->getContainer()->get('program');

        return new SweepstakeEntry(
            $this->getMessagePublisher(),
            $this->participantServiceFactory->getService(),
            $this->participantServiceFactory->getSweepstakeService(),
            $programService->getCatalogService()
        );
    }

    private function getAdjustmentWebhookListener()
    {
        return new AdjustmentWebhookListener(
            $this->getMessagePublisher(),
            $this->participantServiceFactory->getService(),
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

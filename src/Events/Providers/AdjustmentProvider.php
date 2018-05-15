<?php

namespace Events\Providers;

use Events\Listeners\Adjustment\AutoRedemption;
use Events\Listeners\Adjustment\SweepstakeEntry;
use League\Event\ListenerAcceptorInterface;
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

}

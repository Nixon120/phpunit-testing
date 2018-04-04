<?php

namespace Events\Providers;

use Events\Listeners\Organization\RaCreate;
use Events\Listeners\Organization\RaUpdate;
use League\Event\ListenerAcceptorInterface;
use Services\Organization\ServiceFactory;

class OrganizationRaProvider extends AbstractProvider
{
    /**
     * @var ListenerAcceptorInterface
     */
    private $acceptor;

    /**
     * @var ServiceFactory
     */
    private $organizationServiceFactory;

    public function provideListeners(ListenerAcceptorInterface $acceptor)
    {
        $this->acceptor = $acceptor;
        $this->organizationServiceFactory = $this->getContainer()->get('organization');

        $this->addCreateListeners();
        $this->addUpdateListeners();
    }

    private function addCreateListeners()
    {
        $organizationService = $this->organizationServiceFactory->getService();
        $raEvents = ['Organization.create', 'Organization.create.RaCreate'];

        array_walk($raEvents, function ($eventName) use ($organizationService) {
            $this->acceptor->addListener(
                $eventName,
                new RaCreate($this->getMessagePublisher(), $this->getRaClient(), $organizationService)
            );
        });
    }

    private function addUpdateListeners()
    {
        $organizationService = $this->organizationServiceFactory->getService();
        $raEvents = ['Organization.update', 'Organization.update.RaCreate'];

        array_walk($raEvents, function ($eventName) use ($organizationService) {
            $this->acceptor->addListener(
                $eventName,
                new RaUpdate($this->getMessagePublisher(), $this->getRaClient(), $organizationService)
            );
        });
    }
}

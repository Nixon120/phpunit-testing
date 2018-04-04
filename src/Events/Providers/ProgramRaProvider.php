<?php
namespace Events\Providers;

use Events\Listeners\Program\RaCreate;
use Events\Listeners\Program\RaUpdate;
use League\Event\ListenerAcceptorInterface;
use Services\Program\ServiceFactory;

class ProgramRaProvider extends AbstractProvider
{
    /**
     * @var ListenerAcceptorInterface
     */
    private $acceptor;

    /**
     * @var ServiceFactory
     */
    private $programServiceFactory;

    public function provideListeners(ListenerAcceptorInterface $acceptor)
    {
        $this->acceptor = $acceptor;
        $this->programServiceFactory = $this->getContainer()->get('program');

        $this->addCreateListeners();
        $this->addUpdateListeners();
    }

    private function addCreateListeners()
    {
        $programService = $this->programServiceFactory->getService();
        $raEvents = ['Program.create', 'Program.create.RaCreate'];

        array_map(function ($eventName) use ($programService) {
            $this->acceptor->addListener(
                $eventName,
                new RaCreate($this->getMessagePublisher(), $this->getRaClient(), $programService)
            );
        }, $raEvents);
    }

    private function addUpdateListeners()
    {
        $programService = $this->programServiceFactory->getService();
        $raEvents = ['Program.update', 'Program.update.RaCreate'];

        array_map(function ($eventName) use ($programService) {
            $this->acceptor->addListener(
                $eventName,
                new RaUpdate($this->getMessagePublisher(), $this->getRaClient(), $programService)
            );
        }, $raEvents);
    }
}

<?php
namespace Events\Providers;

use Events\Listeners\Report\Request;
use League\Event\ListenerAcceptorInterface;
use Services\Report\ServiceFactory;

class ReportProvider extends AbstractProvider
{
    /**
     * @var ListenerAcceptorInterface
     */
    private $acceptor;

    /**
     * @var ServiceFactory
     */
    private $reportServiceFactory;

    public function provideListeners(ListenerAcceptorInterface $acceptor)
    {
        $this->acceptor = $acceptor;
        $this->reportServiceFactory = $this->getContainer()->get('report');

        $this->addReportRequestListener();
    }

    private function addReportRequestListener()
    {
        $raEvents = ['Report.request', 'Report.request.generate'];

        array_map(function ($eventName) {
            $this->acceptor->addListener(
                $eventName,
                new Request(
                    $this->getMessagePublisher(),
                    $this->reportServiceFactory
                )
            );
        }, $raEvents);
    }
}

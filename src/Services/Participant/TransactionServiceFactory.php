<?php

namespace Services\Participant;

use AllDigitalRewards\Services\Catalog\Client;
use Events\EventPublisherFactory;
use Psr\Container\ContainerInterface;
use Repositories\BalanceRepository;
use Repositories\TransactionRepository;
use Repositories\ParticipantRepository;
use Services\AbstractServiceFactory;

class TransactionServiceFactory extends AbstractServiceFactory
{
    public function __invoke()
    {
        return new Transaction(
            new TransactionRepository(
                $this->getContainer()->get('database'),
                $this->getCatalogService(),
                $this->getProgramCatalogService()
            ),
            new ParticipantRepository($this->getContainer()->get('database'), $this->getCatalogService()),
            new BalanceRepository($this->getContainer()->get('database')),
            $this->getEventPublisher()
        );
    }
}

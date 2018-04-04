<?php

namespace Services\Participant;

use AllDigitalRewards\Services\Catalog\Client;
use Repositories\BalanceRepository;
use Repositories\ContactRepository;
use Repositories\DomainRepository;
use Repositories\OrganizationRepository;
use Repositories\ParticipantRepository;
use Repositories\ProgramRepository;
use Repositories\SweepstakeRepository;
use Services\AbstractServiceFactory;
use Services\Organization\Domain;
use Services\Organization\NestedSet\NestedSet;
use Services\Organization\UpdateOrganizationModel;
use Services\Program\Program;
use Services\Program\Sweepstake;

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * @var Transaction
     */
    private $transactionService;

    /**
     * @var Sweepstake
     */
    private $sweepstakeService;

    public function getService(): Participant
    {
        return new Participant($this->getParticipantRepository());
    }

    public function getProgramService(): Program
    {
        return new Program($this->getProgramRepository(), $this->getContactRepository(), $this->getEventPublisher());
    }

    public function getSweeptakeRepository(): SweepstakeRepository
    {
        return new SweepstakeRepository($this->getDatabase());
    }

    public function getOrganizationService(): UpdateOrganizationModel
    {
        return new UpdateOrganizationModel(
            $this->getOrganizationRepository(),
            $this->getDomainService()->repository,
            $this->getContactRepository(),
            $this->getNestedSet(),
            $this->getEventPublisher()
        );
    }

    private function getContactRepository()
    {
        return new ContactRepository($this->getDatabase());
    }

    public function getNestedSet(): NestedSet
    {
        $tree = new NestedSet($this->getDatabase());
        $tree->setTable('Organization');
        $tree->setDescriptor('name');
        return $tree;
    }

    public function getDomainService(): Domain
    {
        $repository = new DomainRepository($this->getDatabase());
        return new Domain($repository);
    }

    public function getSweepstakeService(): Sweepstake
    {
        if ($this->sweepstakeService === null) {
            $this->sweepstakeService = new Sweepstake($this->getSweeptakeRepository(), $this->getBalanceService());
        }

        return $this->sweepstakeService;
    }

    public function getTransactionService(): Transaction
    {
        if ($this->transactionService === null) {
            $transactionServiceFactory = new TransactionServiceFactory($this->getContainer());
            $this->transactionService = $transactionServiceFactory();
        }

        return $this->transactionService;
    }

    public function getBalanceService(): Balance
    {
        $balanceRepository = new BalanceRepository($this->getContainer()->get('database'));
        $participantRepository = $this->getParticipantRepository();
        //@TODO let's swap this up ? Maybe have a whole service for transaction ? Makes sense.. maybe
        return new Balance($balanceRepository, $participantRepository, $this->getEventPublisher());
    }
}

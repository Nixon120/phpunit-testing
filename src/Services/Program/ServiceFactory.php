<?php

namespace Services\Program;

use Repositories\BalanceRepository;
use Repositories\ContactRepository;
use Repositories\ParticipantRepository;
use Repositories\ProductRepository;
use Repositories\SweepstakeRepository;
use Services\AbstractServiceFactory;
use Services\Participant\Balance;
use Services\Participant\Transaction;
use Services\Participant\TransactionServiceFactory;
use Services\Product\ProductRead;

class ServiceFactory extends AbstractServiceFactory
{
    private $sweepstakeRepository;
    /**
     * @var Transaction
     */
    private $transactionService;

    public function getService(): Program
    {
        return new Program(
            $this->getProgramRepository(),
            $this->getContactRepository(),
            $this->getEventPublisher()
        );
    }

    public function getSweepstakeRepository(): SweepstakeRepository
    {
        if ($this->sweepstakeRepository === null) {
            $this->sweepstakeRepository = new SweepstakeRepository($this->getDatabase());
        }

        return $this->sweepstakeRepository;
    }

    private function getContactRepository()
    {
        return new ContactRepository($this->getDatabase());
    }

    public function getProductService(): ProductRead
    {
        $repository = new ProductRepository(
            $this->getDatabase(),
            $this->getCatalogService()
        );
        return new ProductRead($repository);
    }

    public function getBalanceService(): Balance
    {
        $balanceRepository = new BalanceRepository($this->getContainer()->get('database'));
        $participantRepository = new ParticipantRepository(
            $this->getContainer()->get('database'),
            $this->getCatalogService()
        );

        //@TODO let's swap this up ? Maybe have a whole service for transaction ? Makes sense.. maybe
        return new Balance(
            $balanceRepository,
            $participantRepository,
            $this->getEventPublisher()
        );
    }

    public function getSweepstakeService(): Sweepstake
    {
        return new Sweepstake(
            $this->getSweepstakeRepository(),
            $this->getTransactionService()
        );
    }

    public function getTransactionService(): Transaction
    {
        if ($this->transactionService === null) {
            $transactionServiceFactory = new TransactionServiceFactory($this->getContainer());
            $this->transactionService = $transactionServiceFactory();
        }

        return $this->transactionService;
    }
}

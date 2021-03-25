<?php

use AllDigitalRewards\AMQP\MessagePublisher;
use Entities\Participant;
use PHPUnit\Framework\TestCase;
use Repositories\BalanceRepository;
use Repositories\ParticipantRepository;
use Repositories\TransactionRepository;
use Services\Participant\Transaction;

class TransactionServiceTest extends TestCase
{
    public function testInsertParticipantTransactionWithNoProductsReturnsNull()
    {
        $mockTransactionRepository = $this->createMock(TransactionRepository::class);
        $mockParticipantRepository = $this->createMock(ParticipantRepository::class);
        $mockBalanceRepository = $this->createMock(BalanceRepository::class);
        $mockMessagePublisher = $this->createMock(MessagePublisher::class);
        $transactionService = new Transaction(
            $mockTransactionRepository,
            $mockParticipantRepository,
            $mockBalanceRepository,
            $mockMessagePublisher
        );
        $participant = new Participant();
        $participant->setId(1);
        $participant->setUniqueId('joesuuid');
        $data['products'] = ['VVISA01', 'VVISA02'];
        $response = $transactionService->insertParticipantTransaction($participant, []);
        $error = ['No Products were included in transaction.'];
        $mockTransactionRepository->method('setErrors')
            ->with($error);
        $this->assertSame(null, $response);
    }
}

<?php


class TransactionRepositoryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \PDO
     */
    private $mockPdo;

    /**
     * @var \PDOStatement
     */
    private $mockStatement;

    /**
     * @var Client
     */
    public $client;


    private function getMockPdo()
    {
        if ($this->mockPdo === null) {
            $this->mockPdo = $this->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods(['prepare', 'ping'])
                ->getMock();
        }
        return $this->mockPdo;
    }

    private function getMockStatement()
    {
        if ($this->mockStatement === null) {
            $this->mockStatement = $this->getMockBuilder(\PDOStatement::class)
                ->disableOriginalConstructor()
                ->setMethods(['execute', 'setFetchMode', 'query', 'fetch'])
                ->getMock();
        }
        return $this->mockStatement;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\AllDigitalRewards\Services\Catalog\Client
     */
    private function getMockClient()
    {
        if (!$this->client) {
            $this->client = $this
                ->getMockBuilder(\AllDigitalRewards\Services\Catalog\Client::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->client;
    }

    /**
     * @return \Repositories\TransactionRepository
     */
    private function getTransactionRepository()
    {
        $repository = new \Repositories\TransactionRepository(
            $this->getMockPdo(),
            $this->getMockClient()
        );

        return $repository;
    }

    public function testGetTransactionItemRefundByGuid()
    {
        $this->getMockPdo()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $this->getMockStatement()
            ->expects($this->once())
            ->method('execute')
            ->with(['aguid']);

        $this->getMockStatement()
            ->expects($this->once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, \Entities\TransactionItemRefund::class)
            ->willReturn(1);

        $refundMock = new \Entities\TransactionItemRefund;
        $this->getMockStatement()
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(($refundMock));

        $repository = $this->getTransactionRepository();
        $this->assertSame($refundMock, $repository->getTransactionItemRefund('aguid'));

    }

    public function testGetTransactionItemRefundById()
    {
        $this->getMockPdo()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $this->getMockStatement()
            ->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->getMockStatement()
            ->expects($this->once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, \Entities\TransactionItemRefund::class)
            ->willReturn(1);

        $refundMock = new \Entities\TransactionItemRefund;
        $this->getMockStatement()
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(($refundMock));

        $repository = $this->getTransactionRepository();
        $this->assertSame($refundMock, $repository->getTransactionItemRefund(1));

    }

    public function testCreateTransactionItemRefund()
    {
        $this->getMockPdo()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $refundMock = new \Entities\TransactionItemRefund;
        $refundMock->setTransactionId(1);
        $refundMock->setTransactionItemId(10);
        $refundMock->setNotes('yolo');
        $aRefund['id'] = null;
        $aRefund = array_values($aRefund);
        $this->getMockStatement()
            ->expects($this->once())
            ->method('execute')
            ->with();

        $repository = $this->getTransactionRepository();
        $this->assertSame(true, $repository->createTransactionItemRefund($refundMock));

    }
}

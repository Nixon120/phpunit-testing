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

    public function testGetTransactionItemReturnByGuid()
    {
        $this->getMockPdo()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $user = new \Entities\User;
        $user->setId(1);
        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, $this->isType('string'));

        $returnMock = new \Entities\TransactionItemReturn;
        $returnMock->setUserId(1);
        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($returnMock, $user);

        $repository = $this->getTransactionRepository();
        $this->assertSame($returnMock, $repository->getTransactionItemReturn('aguid'));

    }

    public function testGetTransactionItemReturnById()
    {
        $this->getMockPdo()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $user = new \Entities\User;
        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, $this->isType('string'));

        $returnMock = new \Entities\TransactionItemReturn;
        $this->getMockStatement()
            ->expects($this->exactly(2))
            ->method('fetch')
            ->willReturn($returnMock, $user);

        $repository = $this->getTransactionRepository();
        $this->assertSame($returnMock, $repository->getTransactionItemReturnById(1));

    }

    public function testCreateTransactionItemReturn()
    {
        $this->getMockPdo()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($this->getMockStatement());

        $returnMock = new \Entities\TransactionItemReturn;
        $returnMock->setTransactionId(1);
        $returnMock->setTransactionItemId(10);
        $returnMock->setNotes('yolo');
        $aReturn['id'] = null;
        $aReturn = array_values($aReturn);
        $this->getMockStatement()
            ->expects($this->once())
            ->method('execute')
            ->with();

        $repository = $this->getTransactionRepository();
        $this->assertSame(true, $repository->createTransactionItemReturn($returnMock));

    }
}

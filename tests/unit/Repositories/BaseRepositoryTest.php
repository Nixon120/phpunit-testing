<?php

class BaseRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public $mockDatabase;

    private function getPdoStatementMock()
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "fetchAll", "setFetchMode"])
            ->getMock();
    }

    private function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    private function getMockRepository()
    {
        return new \Repositories\UserRepository($this->getMockDatabase());
    }

    public function testRepositoryDelete()
    {
        $sthMock = $this->getPdoStatementMock();

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'))
            ->will($this->returnValue(true));

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $repository = $this->getMockRepository();
        $this->assertTrue($repository->delete(1));
    }

    public function testGetLastInsertId()
    {
        $this->getMockDatabase()
            ->expects($this->once())
            ->method('lastInsertId')
            ->will($this->returnValue(1));

        $repository = $this->getMockRepository();

        $this->assertEquals($repository->getLastInsertId(), 1);
    }

    public function testBatch()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'))
            ->will($this->returnValue(true));

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('beginTransaction');

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('commit')
            ->will($this->returnValue(true));

        $repository = $this->getMockRepository();

        $user = new \Entities\User();
        $user->setId(1);
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $user->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->assertTrue($repository->batch([$user]));
        $this->assertTrue($repository->batch([$user], true));
    }

    public function testBatchException()
    {
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'))
            ->will($this->throwException(new PDOException));

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('beginTransaction');

        $repository = $this->getMockRepository();

        $user = new \Entities\User;
        $user->setId(1);
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $user->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->assertFalse($repository->batch([$user]));
    }

    private function getMockUser()
    {
        $date = (new DateTime())->format('Y-m-d H:i:s');
        $user = new \Entities\User;
        $user->setId(1);
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCreatedAt($date);
        $user->setUpdatedAt($date);
        return $user;
    }

    private function getMockStatement()
    {

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->once())
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, \Entities\User::class);

        return $sthMock;
    }

    public function testQueryResults()
    {
        $sthMock = $this->getMockStatement();

        $sthMock->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($this->getMockUser()));

        $repository = $this->getMockRepository();
        $this->assertEquals($repository->query('', [], \Entities\User::class), $this->getMockUser());
    }

    public function testQueryNoResults()
    {
        $sthMock = $this->getMockStatement();
        $sthMock->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(null));

        $repository = $this->getMockRepository();
        $this->assertEquals($repository->query('', [], \Entities\User::class), null);
    }
}

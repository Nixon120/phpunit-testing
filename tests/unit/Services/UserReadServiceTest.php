<?php

class UserReadServiceTest extends AbstractUserServiceTest
{
    public function testUserGetById()
    {
        $factory = $this->getUserServiceFactory();
        $reader = $factory->getUserRead();

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(3))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(3))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(3))
            ->method('setFetchMode');

        $sthMock->expects($this->exactly(3))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                $this->getUserEntity(),
                $this->getUserOrganizationEntity(),
                null
            ));

        $user = $reader->getById(1);
        $row = $this->getMockUserRow();
        $row['password'] = $user->getPassword();
        $row['updated_at'] = $user->getUpdatedAt();
        $this->assertSame($user->toArray(), $row);
        $this->assertSame($reader->getById(1), null);
        $this->assertSame($user->getName(), 'John Smith');
        $this->assertSame($user->getFirstname(), 'John');
        $this->assertSame($user->getLastname(), 'Smith');
        $this->assertSame($user->getEmailAddress(), 'john+smith@alldigitalrewards.com');
    }

    public function testUserGetCollection()
    {
        $factory = $this->getUserServiceFactory();
        $reader = $factory->getUserRead();

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
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, \Entities\User::class)
            ->will($this->returnValue($this->getMockUserResult()));

        $filters = [
            'role' => 'superadmin',
            'email_address' => 'johnsmith@alldigitalrewards.com',
            'organization' => 'helloworld',
            'name' => 'johnsmith'
        ];

        $normalizer = new \Controllers\User\InputNormalizer($filters);
        $result = $reader->get($normalizer);
        $this->assertTrue(count($result) === 10);
    }

    private function getMockUserResult()
    {
        return [
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow()),
            new \Entities\User($this->getMockUserResultRow())
        ];
    }

    private function getMockUserResultRow()
    {
        return [
            'id' => 1,
            'organization_reference' => 'ORG123',
            'email_address' => 'john+smith@alldigitalrewards.com',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'active' => 1,
            'role' => 'superadmin',
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null
        ];
    }
}

<?php

class UserModifyServiceTest extends AbstractUserServiceTest
{
    public function testValidUserCreate()
    {
        $factory = $this->getUserServiceFactory();
        $modify = $factory->getUserModify();

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(5))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $this->getMockDatabase()
            ->expects($this->once())
            ->method('lastInsertId')
            ->will($this->returnValue(1));

        $sthMock->expects($this->exactly(5))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(4))
            ->method('setFetchMode');

        $this->getUserEntityWithOrg()->setOrganization($this->getUserOrganizationEntity());

        $sthMock->expects($this->exactly(4))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                $this->getUserOrganizationEntity(),
                false,
                $this->getUserEntityWithOrg(),
                $this->getUserOrganizationEntity(),
                null
            ));

        $data = [
            'email_address' => 'john+smith@alldigitalrewards.com',
            'password' => 'password',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'role' => 'superadmin',
            'invite_token' => null,
            'active' => 1,
            'organization' => 'organizationtest'
        ];

        $user = $modify->insert($data);

        $row = $this->getMockUserRowWithOrg();
        $row['password'] = $user->getPassword();
        $row['updated_at'] = $user->getUpdatedAt();
        $this->assertSame($user->toArray(), $row);
    }

    public function testInValidUserCreate()
    {
        $factory = $this->getUserServiceFactory();
        $modify = $factory->getUserModify();

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(2))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(2))
            ->method('setFetchMode');

        $sthMock->expects($this->exactly(2))
            ->method('fetch')
            ->will($this->returnValue($this->getUserOrganizationEntity()));

        $data = [
            'email_address' => 'john+smith@alldigitalrewards.com',
            'password' => 'password',
            'role' => 'superadmin',
            'active' => 1,
            'organization' => 'testorg'
        ];

        $success = $modify->insert($data);
        $this->assertFalse($success);
    }

    public function testValidUserUpdate()
    {
        $factory = $this->getUserServiceFactory();
        $modify = $factory->getUserModify();

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(7))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $this->getMockDatabase()
            ->expects($this->exactly(1))
            ->method('beginTransaction');

        $this->getMockDatabase()
            ->expects($this->exactly(1))
            ->method('commit')
            ->will($this->returnValue(true));

        $sthMock->expects($this->exactly(7))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(6))
            ->method('setFetchMode');

        $u = $this->getUserEntity();
        $sthMock->expects($this->exactly(6))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                $this->getUserOrganizationEntity(),
                $this->getUserEntity(),
                false,
                $this->getUserOrganizationEntity(),
                $this->getUserEntity()
            ));

        $data = [
            'email_address' => 'john+smith@alldigitalrewards.com',
            'organization' => 'org123',
            'password' => 'password2'
        ];

        $user = $modify->update(1, $data);

        $row = $this->getMockUserRow();
        $row['password'] = $user->getPassword();
        $row['updated_at'] = $user->getUpdatedAt();
        $this->assertSame($user->toArray(), $row);
    }

    public function testInValidUserUpdate()
    {
        $factory = $this->getUserServiceFactory();
        $modify = $factory->getUserModify();

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
                $this->getUserOrganizationEntity()
            ));

        $data = [
            'email_address' => null,
            'password' => 'password',
            'organization' => null
        ];

        $success = $modify->update(1, $data);
        $this->assertFalse($success);
        $this->assertTrue(count($modify->getErrors()) === 3);
    }
}

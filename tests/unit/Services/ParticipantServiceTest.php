<?php

use AllDigitalRewards\StatusEnum\StatusEnum;

class ParticipantServiceTest extends AbstractParticipantServiceTest
{
    public function testValidParticipantCreate()
    {
        $factory = $this->getParticipantServiceFactory();
        $participantService = $factory->getService();
        $participantService->repository->setParticipantStatusRepo($this->getMockParticipantStatusRepo());
        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(29))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($sthMock));

        $sthMock->expects($this->exactly(29))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(21))
            ->method('setFetchMode');

        $sthMock->expects($this->exactly(21))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake(),
                false,
                $this->getParticipantEntity(),
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake(),
                $this->getParticipantEntity(),
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake()
            ));

        $sthMock->expects($this->exactly(2))
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, \Entities\ParticipantMeta::class)
            ->will($this->returnValue($this->getParticipantMetaEntityCollection()));

        $data = [
            'email_address' => 'john+smith@alldigitalrewards.com',
            'unique_id' => 'johnsmithtest',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'phone' => '1231231234',
            'password' => 'password',
            'active' => 1,
            'status' => 1,
            'address' => [
                'firstname' => 'John',
                'lastname' => 'Smith',
                'address1' => '123 Acme St',
                'address2' => '',
                'city' => 'Beverly Hills',
                'state' => 'CA',
                'zip' => '90210'
            ],
            'program' => 'programtest',
            'organization' => 'organizationtest',
            'meta' => [
                ['hello' => 'world']
            ]
        ];

        $removedData = $data;
        unset($removedData['program'], $removedData['organization']);
        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('getHydratedStatusRequest')
            ->with($this->isType('array'))
            ->willReturn($removedData);

        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('hasValidStatus')
            ->with(1)
            ->willReturn(true);

        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('saveParticipantStatus')
            ->with($this->isType('object'), 1)
            ->willReturn(true);

        $participant = $participantService->insert($data, 'system');
        $row = $this->getMockParticipantRow();
        $row['password'] = $participant->getPassword();
        $row['updated_at'] = $participant->getUpdatedAt();
        $row['status'] = null;

        $this->assertSame($participant->toArray(), $row);
    }

    public function testValidParticipantUpdate()
    {
        $factory = $this->getParticipantServiceFactory();
        $participantService = $factory->getService();
        $participantService->repository->setParticipantStatusRepo($this->getMockParticipantStatusRepo());

        $sthMock = $this->getPdoStatementMock();

        $this->getMockDatabase()
            ->expects($this->exactly(28))
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

        $sthMock->expects($this->exactly(28))
            ->method('execute')
            ->with($this->isType('array'));

        $sthMock->expects($this->exactly(20))
            ->method('setFetchMode');

        $sthMock->expects($this->exactly(20))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                $this->getParticipantEntity(),
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake(),
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake(),
                $this->getParticipantEntity(),
                $this->getParticipantProgramEntity(),
                $this->getParticipantOrganizationEntity(),
                $this->getProgramDomainEntity(),
                $this->getProgramAutoRedemptionEntity(),
                $this->getProgramContactEntity(),
                $this->getProgramSweepstake()
            ));

        $sthMock->expects($this->exactly(2))
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, \Entities\ParticipantMeta::class)
            ->will($this->returnValue($this->getParticipantMetaEntityCollection()));

        $data = [
            'email_address' => 'johnsmith@alldigitalrewards.com',
            'program' => 'ellopoppet',
            'unique_id' => 'johnsmithtest',
            'firstname' => 'John',
            'lastname' => 'Smith',
            'phone' => '1231231234',
            'active' => 1,
            'status' => 1,
            'password' => 'password',
            'address' => [
                'firstname' => 'John',
                'lastname' => 'Smith',
                'address1' => '123 Acme St',
                'address2' => '',
                'city' => 'Beverly Hills',
                'state' => 'CA',
                'zip' => '90210'
            ],
            'meta' => [
                ['hello' => 'world']
            ]
        ];
        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('getHydratedStatusRequest')
            ->with($this->isType('array'))
            ->willReturn($data);

        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('hasValidStatus')
            ->with(1)
            ->willReturn(true);

        $this->getMockParticipantStatusRepo()
            ->expects($this->once())
            ->method('saveParticipantStatus')
            ->with($this->isType('object'), 1)
            ->willReturn(true);

        $participant = $participantService->update(1, $data, 'system');

        $row = $this->getMockParticipantRow();
        $row['password'] = $participant->getPassword();
        $row['updated_at'] = $participant->getUpdatedAt();
        $row['status'] = null;
        $this->assertSame($participant->toArray(), $row);
    }

    public function testParticipantCreateRequestActiveInputIsValidReturnsFalse()
    {
        $this->assertFalse(in_array(2, [0, '0', 1, '1', true, false], true));
        $this->assertFalse(in_array('true', [0, '0', 1, '1', true, false], true));
        $this->assertFalse(in_array('false', [0, '0', 1, '1', true, false], true));
        $this->assertFalse(in_array('ACTIVE', [0, '0', 1, '1', true, false], true));
    }

    public function testParticipantCreateRequestActiveInputIsValidReturnsTrue()
    {
        $this->assertTrue(in_array(0, [0, '0', 1, '1', true, false], true));
        $this->assertTrue(in_array('0', [0, '0', 1, '1', true, false], true));
        $this->assertTrue(in_array(1, [0, '0', 1, '1', true, false], true));
        $this->assertTrue(in_array('1', [0, '0', 1, '1', true, false], true));
        $this->assertTrue(in_array(true, [0, '0', 1, '1', true, false], true));
        $this->assertTrue(in_array(false, [0, '0', 1, '1', true, false], true));
    }

    public function testParticipantCreateRequestStatusInputIsValidReturnsFalse()
    {
        $this->assertFalse((new StatusEnum())->isValidStatus(11));
        $this->assertFalse((new StatusEnum())->isValidStatus(9));
        $this->assertFalse((new StatusEnum())->isValidStatus('NOTACTIVE'));
        $this->assertFalse((new StatusEnum())->isValidStatus(false));
    }

    public function testParticipantCreateRequestStatusInputIsValidReturnsTrue()
    {
        $this->assertTrue((new StatusEnum())->isValidStatus(1));
        $this->assertTrue((new StatusEnum())->isValidStatus(2));
        $this->assertTrue((new StatusEnum())->isValidStatus(3));
        $this->assertTrue((new StatusEnum())->isValidStatus(4));
        $this->assertTrue((new StatusEnum())->isValidStatus(5));
        $this->assertTrue((new StatusEnum())->isValidStatus('1'));
        $this->assertTrue((new StatusEnum())->isValidStatus('2'));
        $this->assertTrue((new StatusEnum())->isValidStatus('3'));
        $this->assertTrue((new StatusEnum())->isValidStatus('4'));
        $this->assertTrue((new StatusEnum())->isValidStatus('5'));
        $this->assertTrue((new StatusEnum())->isValidStatus('ACTIVE'));
        $this->assertTrue((new StatusEnum())->isValidStatus('INACTIVE'));
        $this->assertTrue((new StatusEnum())->isValidStatus('HOLD'));
        $this->assertTrue((new StatusEnum())->isValidStatus('CANCELLED'));
        $this->assertTrue((new StatusEnum())->isValidStatus('DATADEL'));
    }

    public function testParticipantStatusRepoActiveInputIsSetTo1ReturnsActiveAndStatus1()
    {
        $expected = [
          'active' => 1,
          'status' => 1,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 1]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoActiveInputIsSetTo0ReturnsActive0AndStatus3()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::INACTIVE,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 0]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetTo1ReturnsActive0AndStatus1()
    {
        $expected = [
          'active' => 1,
          'status' => StatusEnum::ACTIVE,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['status' => StatusEnum::ACTIVE]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetToHoldReturnsActive1AndStatus2()
    {
        $expected = [
          'active' => 1,
          'status' => StatusEnum::HOLD,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['status' => 2]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetToInactiveReturnsActive0AndStatus3()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::INACTIVE,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['status' => 3]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetToCancelledReturnsActive0AndStatus4()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::CANCELLED,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['status' => 4]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetToDataDelReturnsActive0AndStatus5()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::DATADEL,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['status' => 5]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetAndTakesPrecedentSettingToDataDelReturnsActive0AndStatus5()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::DATADEL,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 1,'status' => 5]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetAndTakesPrecedentSettingToCancelledReturnsActive0AndStatus4()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::CANCELLED,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 1,'status' => 4]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetAndTakesPrecedentSettingToInactiveReturnsActive0AndStatus3()
    {
        $expected = [
          'active' => 0,
          'status' => StatusEnum::INACTIVE,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 1,'status' => 3]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetAndTakesPrecedentSettingToHoldReturnsActive1AndStatus2()
    {
        $expected = [
          'active' => 1,
          'status' => StatusEnum::HOLD,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 0,'status' => 2]);
        $this->assertEquals($expected, $return);
    }

    public function testParticipantStatusRepoStatusInputIsSetAndTakesPrecedentSettingToActiveReturnsActive1AndStatus1()
    {
        $expected = [
          'active' => 1,
          'status' => StatusEnum::ACTIVE,
        ];

        $participantStatusRepo = new \Repositories\ParticipantStatusRepository($this->getMockDatabase());
        $return = $participantStatusRepo->getHydratedStatusRequest(['active' => 0,'status' => 1]);
        $this->assertEquals($expected, $return);
    }
}

<?php

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
            ->willReturn([1,$removedData]);

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
            ->willReturn([1,$data]);

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
}

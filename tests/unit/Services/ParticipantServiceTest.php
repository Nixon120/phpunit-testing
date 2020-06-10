<?php

class ParticipantServiceTest extends AbstractParticipantServiceTest
{
    public function testValidParticipantCreate()
    {
        $factory = $this->getParticipantServiceFactory();
        $participantService = $factory->getService();

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
            'frozen' => 0,
            'address' => [
                'firstname' => 'John',
                'lastname' => 'Smith',
                'address1' => '123 Acme St',
                'address2' => '',
                'city' => 'Beverly Hills',
                'state' => 'CA',
                'zip' => '90210'
            ],
            'program' => 'testprogram',
            'organization' => 'testorganization',
            'meta' => [
                ['hello' => 'world']
            ]
        ];

        $participant = $participantService->insert($data, 'system');
        $row = $this->getMockParticipantRow();
        $row['password'] = $participant->getPassword();
        $row['updated_at'] = $participant->getUpdatedAt();

        $this->assertSame($participant->toArray(), $row);
    }

    public function testValidParticipantUpdate()
    {
        $factory = $this->getParticipantServiceFactory();
        $participantService = $factory->getService();

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

        $participant = $participantService->update(1, $data, 'system');

        $row = $this->getMockParticipantRow();
        $row['password'] = $participant->getPassword();
        $row['updated_at'] = $participant->getUpdatedAt();
        $this->assertSame($participant->toArray(), $row);
    }
}

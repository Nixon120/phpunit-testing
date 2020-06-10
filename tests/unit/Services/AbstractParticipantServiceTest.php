<?php

abstract class AbstractParticipantServiceTest extends \PHPUnit\Framework\TestCase
{
    private $container;

    private $participantServiceFactory;

    public $mockDatabase;

    protected function getPdoStatementMock()
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch", "fetchAll", "setFetchMode"])
            ->getMock();
    }

    protected function getMockDatabase()
    {
        if (!$this->mockDatabase) {
            $this->mockDatabase = $this
                ->getMockBuilder(\PDO::class)
                ->disableOriginalConstructor()
                ->setMethods(["beginTransaction", "commit", "prepare", "lastInsertId"])
                ->getMock();
        }

        return $this->mockDatabase;
    }

    protected function getMockSlimContainer()
    {
        if ($this->container === null) {
            $settings = require __DIR__ . '/../../../src/settings.php';
            $container = new \Slim\Container($settings);
            require __DIR__ . '/../../../src/dependencies.php';
            $this->container = $container;
        }

        return $this->container;
    }

    protected function getParticipantServiceFactory(): \Services\Participant\ServiceFactory
    {
        if ($this->participantServiceFactory === null) {
            $user = new \Entities\User;
            $user->setOrganizationId(1);
            $user->setRole('admin');
            $user->setOrganizationOwnershipIdentificationCollection([1]);
            $user->setProgramOwnershipIdentificationCollection([1]);
            $this->participantServiceFactory = new Services\Participant\ServiceFactory($this->getMockSlimContainer());
            $this->participantServiceFactory->setDatabase($this->getMockDatabase());
            $this->participantServiceFactory->setAuthenticatedUser($user);
        }

        return $this->participantServiceFactory;
    }

    protected function getParticipantOrganizationEntity()
    {
        return new \Entities\Organization($this->getMockOrganizationRow());
    }

    protected function getMockOrganizationRow()
    {
        return [
            'id' => 1,
            'parent_id' => null,
            'username' => 'username',
            'password' => 'password',
            'name' => 'OrganizationTest',
            'lft' => 1,
            'rgt' => 10,
            'lvl' => 1,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'unique_id' => 'organizationtest',
            'company_contact_reference' => null,
            'accounts_payable_contact_reference' => null
        ];
    }

    protected function getParticipantProgramEntity()
    {
        return new \Entities\Program($this->getMockProgramRow());
    }

    protected function getMockProgramRow()
    {
        return [
            'id' => 1,
            'organization_id' => 1,
            'name' => 'Program Test',
            'role' => null,
            'point' => 1000,
            'address1' => null, //@TODO migration to drop
            'address2' => null, //@TODO migration to drop
            'city' => null, //@TODO migration to drop
            'state' => null, //@TODO migration to drop
            'zip' => null, //@TODO migration to drop
            'phone' => null, //@TODO migration to drop
            'url' => 'program-demo',
            'domain_id' => 1,
            'meta' => null, //@TODO migration to drop
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'unique_id' => 'programtest',
            'contact_reference' => 'atestreference',
            'invoice_to' => 'Top Level Client',
            'deposit_amount' => 0,
            'issue_1099' => 0,
            'employee_payroll_file' => 0
        ];
    }

    protected function getProgramDomainEntity()
    {
        return new \Entities\Domain($this->getMockProgramDomainRow());
    }

    protected function getMockProgramDomainRow()
    {
        return [
            'id' => 1,
            'organization_id' => 1,
            'url' => 'alldigtialrewards.com',
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
        ];
    }

    protected function getProgramAutoRedemptionEntity()
    {
        return new \Entities\AutoRedemption($this->getMockProgramAutoRedemptionRow());
    }

    protected function getMockProgramAutoRedemptionRow()
    {
        return [
            'id' => 1,
            'program_id' => 1,
            'sku' => 1,
            'interval' => 1,
            'schedule' => 'daily',
            'all_participant' => 1,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
        ];
    }

    protected function getProgramAutoRedemptionProductEntity()
    {
        return  new \Entities\Product($this->getMockProgramAutoRedemptionProductRow());
    }

    protected function getMockProgramAutoRedemptionProductRow()
    {
        return [
            'id' => 1,
            'feed_id' => 1,
            'category_id' => 3,
            'vendor_category_id' => 501016,
            'unique_id' => '333438363039325053303030333438363039322d3234',
            'wholesale' => 420,
            'retail' => 30,
            'handling' => 2,
            'shipping' => 14.55,
            'name' => 'BergHoff Vision 18/10 SS 8pc Cookware Set, Glass Lids',
            'description' => 'Set includes: 6.25" covered sauce pan, 7" covered sauce pan, 8" covered sauce pan, 5.5" milk warmer, and 10" fry pan. Glass lids for easy monitoring without the need to lift the lid, which helps to save energy and keep valuable nutrients inside the pan. Revolutionary multi-layer base for fast and energy-saving cooking. Even heat distribution throughout complete surface of the base. Fry pan and milk boiler have a multi-layer PFOA-free non-stick coating for easy food release and healthy cooking (use as little oil as you\'d like). Large stay-cool handles.',
            'vendor_code' => 'PS0003486092-24',
            'kg' => 0,
            'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
            'manufacturer' => null,
            'image' => '2152752D-63D7-45B3-B649-D6F1F6CA2997-300.jpg',
            'type' => 'physical',
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null
        ];
    }

    protected function getProgramContactEntity()
    {
        $contact = new \Entities\Contact;
        $contact->hydrate($this->getMockProgramContactRow());
        return $contact;
    }

    protected function getMockProgramContactRow()
    {
        return [
            'id' => 1,
            'firstname' => 'John',
            'lastname' => 'Smith',
            'phone' => '1231231234',
            'email' => 'johnsmith+programmanager@alldigitalrewards.com',
            'address1' => '123 Rockerfella Ave',
            'address2' => 'Apt B',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '90210',
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'reference_id' => 12
        ];
    }

    protected function getParticipantEntity()
    {
        return new \Entities\Participant($this->getMockParticipantRow());
    }

    protected function getMockParticipantRow()
    {
        return [
            'id' => 1,
            'email_address' => 'john+smith@alldigitalrewards.com',
            'password' => '$2y$10$PeyOZDPdszOiuBS4rfuUdu3BB6o73Ze/IRcfNwjPzVPFUwQkl.MIi',
            'unique_id' => 'johnsmithuniqueid',
            'sso' => null,
            'credit' => null,
            'firstname' => 'John',
            'lastname' => 'Smith',
            'address_reference' => null,
            'phone' => '1231231234',
            'birthdate' => null,
            'frozen' => 0,
            'deactivated_at' => null,
            'active' => 1,
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null,
            'organization_id' => 1,
            'program_id' => 1
        ];
    }

    protected function getParticipantMetaEntityCollection()
    {
        return [
            new \Entities\ParticipantMeta($this->getParticipantMetaRow())
        ];
    }

    protected function getParticipantMetaRow()
    {
        return [
            'participant_id' => 1,
            'key' => 'hello',
            'value' => 'world',
            'created_at' => '2017-12-06 01:28:09',
            'updated_at' => null
        ];
    }

    protected function getProgramSweepstake()
    {
        new \Entities\Sweepstake;
    }
}

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
            'logo' => 'data:image/png;base64,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',
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

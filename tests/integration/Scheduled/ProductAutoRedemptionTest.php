<?php

namespace IntegrationTests\Scheduled;

use AllDigitalRewards\Services\Catalog\Entity\Product;
use Entities\AutoRedemption;
use Entities\Program;
use Factories\LoggerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Services\Participant\Participant;
use Services\Participant\Transaction;
use Services\Scheduler\Tasks\ScheduledRedemption;

class ProductAutoRedemptionTest extends TestCase
{
    private $container;

    private $autoRedemption;

    private $scheduledRedemption;

    private $transactionService;

    private $participantService;

    public function testScheduledRedemptionWithRangedProductWithNoParticipants()
    {
        $scheduled = $this->getRangedProductScheduledRedemptionWithNoParticipants();
        $scheduled->run();
        $this->assertEquals('No participants have enough points to auto-redeem.', $scheduled->getOutput());
    }

    public function testScheduledRedemptionWithStaticProductWithNoParticipants()
    {
        $scheduled = $this->getStaticProductScheduledRedemptionWithNoParticipants();
        $scheduled->run();
        $this->assertEquals('No participants have enough points to auto-redeem.', $scheduled->getOutput());
    }

    public function testScheduledRedemptionWithStaticProduct()
    {
        $scheduled = $this->getStaticProductScheduledRedemption();
        $scheduled->run();
        $this->assertEquals('Static product scheduled redemption completed', $scheduled->getOutput());
    }

    public function testScheduledRedemptionWithRangedProduct()
    {
        $scheduled = $this->getRangedProductScheduledRedemption();
        $scheduled->run();
        $this->assertEquals('Ranged product scheduled redemption completed', $scheduled->getOutput());
    }

    public function getMockContainer()
    {
        if ($this->container === null) {
            $settings = require __DIR__ . '/../../../src/settings.php';
            $container = new \Slim\Container($settings);
            require __DIR__ . '/../../../src/dependencies.php';
            $this->container = $container;
        }

        return $this->container;
    }

    public function getMockAutoRedemption()
    {
        if ($this->autoRedemption === null) {
            $this->autoRedemption = new AutoRedemption([
                'program_id' => 1,
                'product_sku' => 'APRODUCTSKU',
                'interval' => 1, //scheduled
                'schedule' => 'minute',
                'all_participant' => 1,
                'active' => 1
            ]);
        }

        return $this->autoRedemption;
    }

    public function getRangedProductScheduledRedemptionWithNoParticipants()
    {
        $this->scheduledRedemption = new ScheduledRedemption();
        $this->scheduledRedemption->setContainer($this->getMockContainer());
        $this->scheduledRedemption->setAutoRedemption($this->getMockAutoRedemption());
        $this->scheduledRedemption->setProduct($this->getMockRangedProduct());
        $this->scheduledRedemption->setProgram($this->getMockProgram());
        $this->scheduledRedemption->setParticipantService($this->getMockEmptyParticipantService());
        $this->scheduledRedemption->setTransactionService($this->getMockTransactionService());

        return $this->scheduledRedemption;
    }

    public function getStaticProductScheduledRedemptionWithNoParticipants()
    {
        $this->scheduledRedemption = new ScheduledRedemption();
        $this->scheduledRedemption->setContainer($this->getMockContainer());
        $this->scheduledRedemption->setAutoRedemption($this->getMockAutoRedemption());
        $this->scheduledRedemption->setProduct($this->getMockStaticProduct());
        $this->scheduledRedemption->setProgram($this->getMockProgram());
        $this->scheduledRedemption->setParticipantService($this->getMockEmptyParticipantService());
        $this->scheduledRedemption->setTransactionService($this->getMockTransactionService());

        return $this->scheduledRedemption;
    }

    public function getStaticProductScheduledRedemption()
    {
        $this->scheduledRedemption = new ScheduledRedemption();
        $this->scheduledRedemption->setContainer($this->getMockContainer());
        $this->scheduledRedemption->setAutoRedemption($this->getMockAutoRedemption());
        $this->scheduledRedemption->setProduct($this->getMockStaticProduct());
        $this->scheduledRedemption->setProgram($this->getMockProgram());
        $this->scheduledRedemption->setParticipantService($this->getMockParticipantService());
        $this->scheduledRedemption->setTransactionService($this->getMockTransactionService());

        return $this->scheduledRedemption;
    }

    public function getRangedProductScheduledRedemption()
    {
        $this->scheduledRedemption = new ScheduledRedemption();
        $this->scheduledRedemption->setContainer($this->getMockContainer());
        $this->scheduledRedemption->setAutoRedemption($this->getMockAutoRedemption());
        $this->scheduledRedemption->setProduct($this->getMockRangedProduct());
        $this->scheduledRedemption->setProgram($this->getMockProgram());
        $this->scheduledRedemption->setParticipantService($this->getMockParticipantService());
        $this->scheduledRedemption->setTransactionService($this->getMockTransactionService());

        return $this->scheduledRedemption;
    }

    /**
     * @return Transaction|MockObject
     */
    public function getMockTransactionService(): Transaction
    {
        if ($this->transactionService === null) {
            $this->transactionService = $this->getMockBuilder(Transaction::class)
                ->disableOriginalConstructor()
                ->getMock();

            $this->transactionService
                ->expects($this->any())
                ->method('insertParticipantTransaction')
                ->withAnyParameters()
                ->willReturn($this->getMockParticipantTransaction());
        }

        return $this->transactionService;
    }

    /**
     * @return \Services\Participant\Participant|MockObject
     */
    public function getMockEmptyParticipantService(): Participant
    {
        if ($this->participantService === null) {
            $this->participantService = $this->getMockBuilder(Participant::class)
                ->disableOriginalConstructor()
                ->getMock();

            $this->participantService
                ->expects($this->any())
                ->method('getProgramParticipantsWithPointsGreaterThan')
                ->withAnyParameters()
                ->willReturn([]);
        }

        return $this->participantService;
    }

    /**
     * @return \Services\Participant\Participant|MockObject
     */
    public function getMockParticipantService(): Participant
    {
        if ($this->participantService === null) {
            $this->participantService = $this->getMockBuilder(Participant::class)
                ->disableOriginalConstructor()
                ->getMock();

            $this->participantService
                ->expects($this->any())
                ->method('getProgramParticipantsWithPointsGreaterThan')
                ->withAnyParameters()
                ->willReturn($this->getMockParticipantCollection());

            $this->participantService
                ->expects($this->any())
                ->method('getSingle')
                ->withAnyParameters()
                ->willReturn($this->getMockParticipant());
        }

        return $this->participantService;
    }

    public function getMockRangedProduct()
    {
        return new Product([
            'price_total' => 10,
            'sku' => 'APRODUCTSKU',
            'price_ranged' => 1,
            'min' => 0,
            'max' => 100
        ]);
    }

    public function getMockStaticProduct()
    {
        return new Product([
            'price_total' => 10,
            'sku' => 'APRODUCTSKU',
            'price_ranged' => 0
        ]);
    }

    public function getMockProgram()
    {
        return new Program([
            'unique_id' => 'AUNIQUEID',
            'point' => 1000
        ]);
    }

    public function getMockParticipant()
    {

        $participant = new \Entities\Participant;
        $participant->setUniqueId('AUNIQUEID');
        $participant->setCredit(10000);
        $participant->setFirstname('John');
        $participant->setLastname('Doe');
        $participant->setAddress([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address1' => '123 Acme Dr',
            'address2' => 'Suite #3',
            'city' => 'Beverly Hills',
            'state' => 'CA',
            'zip' => 90210,
            'country' => 840
        ]);

        return $participant;
    }

    public function getMockParticipantCollection()
    {
        return [
            $this->getMockParticipant()
        ];
    }

    public function getMockParticipantTransaction()
    {
        return new \Entities\Transaction;
    }
}

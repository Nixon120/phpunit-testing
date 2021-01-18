<?php

namespace unit\Entities;

use Entities\Adjustment;
use Entities\Participant;
use Entities\Program;
use PDO;
use PHPUnit\Framework\TestCase;
use Repositories\BalanceRepository;

class AdjustmentTest extends TestCase
{
    /**
     * @var PDO
     */
    private $mockPdo;
    /**
     * @var BalanceRepository
     */
    private $balanceRepo;

    public function testCreditAmountWithProgramPoint1IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '10000000.00000', 1);
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 9999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint1IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '9999999.00000', 1);
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testCreditAmountWithProgramPoint10IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '100000000.00000', 10);
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 99999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint10IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '99999999.00000', 10);
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testCreditAmountWithProgramPoint100IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '1000000000.00000', 100);
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 999999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint100IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '999999999.00000', 100);
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testCreditAmountWithProgramPoint1000IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '10000000000.00000');
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 9999999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint1000IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '9999999999.00000');
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testCreditAmountWithProgramPoint10000IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '100000000000.00000', 10000);
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 99999999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint10000IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '99999999999.00000', 10000);
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testCreditAmountWithProgramPoint100000IsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '1000000000000.00000', 100000);
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 999999999999 points', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountWithProgramPoint100000IsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '999999999999.00000', 100000);
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    /**
     * @return BalanceRepository
     */
    private function getBalanceRepo(): BalanceRepository
    {
        if ($this->balanceRepo === null) {
            $this->balanceRepo = new BalanceRepository($this->getMockPdo());
        }
        return $this->balanceRepo;
    }

    private function getMockPdo()
    {
        if ($this->mockPdo === null) {
            $this->mockPdo = $this->getMockBuilder(PDO::class)
                ->disableOriginalConstructor()
                ->setMethods(['prepare', 'ping'])
                ->getMock();
        }
        return $this->mockPdo;
    }

    private function getParticipantProgramEntity(int $pointAmount): Program
    {
        return new Program($this->getMockProgramRow($pointAmount));
    }

    private function getMockProgramRow(int $pointAmount): array
    {
        return [
            'id' => 1,
            'organization_id' => 1,
            'name' => 'Program Test',
            'role' => null,
            'point' => $pointAmount,
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

    /**
     * @param string $type
     * @param string $amount
     * @param int $pointAmount
     * @return Adjustment
     */
    private function getAdjustment(string $type, string $amount, int $pointAmount = 1000): Adjustment
    {
        $participant = new Participant();
        $participant->setId(1);
        $participant->setUniqueId('Test');
        $participant->setCredit('99999999.00000');
        $participant->setProgram($this->getParticipantProgramEntity($pointAmount));
        $adjustment = new Adjustment($participant);
        $adjustment->setAmount($amount);
        $adjustment->setType($type);
        return $adjustment;
    }
}

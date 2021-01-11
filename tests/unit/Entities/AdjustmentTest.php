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

    public function testCreditAmountIsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('credit', '10001.00000');
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame('amount must be less than or equal to 10000.00000', $this->getBalanceRepo()->getErrors()[0]);
    }

    public function testCreditAmountIsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('credit', '10000.00000');
        $this->assertTrue($this->getBalanceRepo()->validate($adjustment));
    }

    public function testDebitAmountIsValidReturnsFalse()
    {
        $adjustment = $this->getAdjustment('debit', '10000000.00000');
        $this->assertFalse($this->getBalanceRepo()->validate($adjustment));
        $this->assertSame(
            'amount must be less than or equal to 9999999.99999',
            $this->getBalanceRepo()->getErrors()[0]
        );
    }

    public function testDebitAmountIsValidReturnsTrue()
    {
        $adjustment = $this->getAdjustment('debit', '9999999.99999');
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

    private function getParticipantProgramEntity()
    {
        return new Program($this->getMockProgramRow());
    }

    private function getMockProgramRow(): array
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

    /**
     * @param string $type
     * @param string $amount
     * @return Adjustment
     */
    private function getAdjustment(string $type, string $amount): Adjustment
    {
        $participant = new Participant();
        $participant->setId(1);
        $participant->setUniqueId('Test');
        $participant->setCredit('99999999.00000');
        $participant->setProgram($this->getParticipantProgramEntity());
        $adjustment = new Adjustment($participant);
        $adjustment->setAmount($amount);
        $adjustment->setType($type);
        return $adjustment;
    }
}

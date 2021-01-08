<?php

namespace unit\Entities;

use PHPUnit\Framework\TestCase;

class AdjustmentTest extends TestCase
{
    const ACCEPTABLE_CREDIT_LIMIT = '10000.00000';
    const ACCEPTABLE_DEBIT_LIMIT = '9999999.99999';
    
    public function testAmountIsGreaterThanAcceptableCreditLimitReturnsTrue()
    {
        $diff = bcsub(self::ACCEPTABLE_CREDIT_LIMIT, '10000.00001', 5);
        $this->assertTrue(floatval($diff) < 0);
    }

    public function testAmountIsGreaterThanAcceptableCreditLimitReturnsFalse()
    {
        $diff = bcsub(self::ACCEPTABLE_CREDIT_LIMIT, '10000.00000', 5);
        $this->assertFalse(floatval($diff) < 0);
    }

    public function testAmountIsGreaterThanAcceptableDebitLimitReturnsTrue()
    {
        $diff = bcsub(self::ACCEPTABLE_DEBIT_LIMIT, '10000000.00000', 5);
        $this->assertTrue(floatval($diff) < 0);
    }

    public function testAmountIsGreaterThanAcceptableDebitLimitReturnsFalse()
    {
        $diff = bcsub(self::ACCEPTABLE_DEBIT_LIMIT, '9999999.99999', 5);
        $this->assertFalse(floatval($diff) < 0);
    }
}

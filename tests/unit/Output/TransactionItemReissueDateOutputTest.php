<?php

use PHPUnit\Framework\TestCase;

class TransactionItemReissueDateOutputTest extends TestCase
{
    public function testValidatesReissueDateReturnsFalse()
    {
        $this->assertFalse($this->validateDate('08-28-2019'));
    }

    public function testValidatesReissueDateReturnsTrue()
    {
        $this->assertTrue($this->validateDate('2019-08-28'));
    }

    private function validateDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with
        // any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format('Y-m-d') === $date;
    }
}

<?php

use PHPUnit\Framework\TestCase;
use AllDigitalRewards\RewardStack\Services\Participant\StatusEnum\StatusEnum;

class StatusTypeTest extends TestCase
{
    public function testStatusNamesReturnFalse()
    {
        $this->assertFalse(StatusEnum::isValidName('activate'));
        $this->assertFalse(StatusEnum::isValidName('held'));
        $this->assertFalse(StatusEnum::isValidName('cancel'));
        $this->assertFalse(StatusEnum::isValidName('del'));
    }

    public function testStatusNamesReturnTrue()
    {
        $this->assertTrue(StatusEnum::isValidName('active'));
        $this->assertTrue(StatusEnum::isValidName('hold'));
        $this->assertTrue(StatusEnum::isValidName('cancelled'));
        $this->assertTrue(StatusEnum::isValidName('datadel'));
    }

    public function testStatusValuesReturnFalse()
    {
        $this->assertFalse(StatusEnum::isValidValue('1.5'));
        $this->assertFalse(StatusEnum::isValidValue('soemthing'));
        $this->assertFalse(StatusEnum::isValidValue(null));

        //out of scope of declared const #s, as we add more constants this will
        //eventually fail
        $this->assertFalse(StatusEnum::isValidValue(6));
    }

    public function testStatusValuesReturnTrue()
    {
        $this->assertTrue(StatusEnum::isValidValue(1));
        $this->assertTrue(StatusEnum::isValidValue(2));
        $this->assertTrue(StatusEnum::isValidValue(3));
        $this->assertTrue(StatusEnum::isValidValue(4));
    }
}

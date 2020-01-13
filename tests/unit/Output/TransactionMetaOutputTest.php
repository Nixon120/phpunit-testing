<?php

use PHPUnit\Framework\TestCase;

class TransactionMetaOutputTest extends TestCase
{
    use \AllDigitalRewards\RewardStack\Traits\MetaValidationTrait;

    public function testValidatesMetaReturnsFalse()
    {
        $this->assertFalse($this->hasWellFormedMeta($this->badMeta()));
    }

    public function testValidatesMetaReturnsTrue()
    {
        $this->assertTrue($this->hasWellFormedMeta($this->goodMeta()));
    }

    private function goodMeta()
    {
        return [
            [
                "some_key" => "/group_february_fitness_incentive|5c722c4b7be6b8518803d500"
            ],
            [
                "some_key_two" => "someemail@gmail.com"
            ]
        ];
    }

    private function badMeta()
    {
        return [
            [
                "some_key"
            ],
            [
                "some_key_two" => "someemail@gmail.com"
            ]
        ];
    }
}

<?php

use Entities\TransactionMeta;
use PHPUnit\Framework\TestCase;

class TransactionMetaOutputTest extends TestCase
{
    public function testValidatesMetaReturnsFalse()
    {
        $test = new TransactionMeta();
        $this->assertFalse($test->validate($this->badMeta()));
    }

    public function testValidatesMetaReturnsTrue()
    {
        $test = new TransactionMeta();
        $this->assertTrue($test->validate($this->goodMeta()));
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
<?php


use PHPUnit\Framework\TestCase;

class AutoRedemptionTest extends TestCase
{
    /**
     * Regression test since the API returns an integer 1||2 for 'interval' but
     * accepts a string scheduled||anything.  Code has been updated to allow
     * it to accept an integer as well as a string.
     */
    public function testSetIntervalWithInteger()
    {
        $autoRedemption = new \Entities\AutoRedemption(['interval' => 1]);

        $this->assertSame(
            'scheduled',
            $autoRedemption->getInterval()
        );
    }
}


<?php
use PHPUnit\Framework\TestCase;

class SweepstakeEntryTest extends TestCase
{
    public function testDrawingDateIsNotElapsed()
    {
        $entry = new \Entities\SweepstakeEntry();
        $this->assertSame(null, $entry->getSweepstakeDrawId());
    }

}

<?php
use PHPUnit\Framework\TestCase;

class SweepstakeDrawTest extends TestCase
{
    /**
     * Regression to ensure sweepstake drawing is not processed by default
     */
    public function testIsProcessedIsFalseWhenNotSet()
    {
        $draw = new \Entities\SweepstakeDraw;
        $this->assertSame(false, $draw->isProcessed());
    }

    public function testDrawingDateIsElapsed()
    {
        $draw = new \Entities\SweepstakeDraw;
        $date = new \DateTime('-1 day');
        $draw->setDate($date->format('Y-m-d'));
        $this->assertSame(true, $draw->isElapsed());
    }

    public function testDrawingDateIsNotElapsed()
    {
        $draw = new \Entities\SweepstakeDraw;
        $date = new \DateTime('+1 day');
        $draw->setDate($date->format('Y-m-d'));
        $this->assertSame(false, $draw->isElapsed());
    }

}

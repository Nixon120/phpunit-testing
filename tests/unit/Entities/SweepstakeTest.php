<?php
use PHPUnit\Framework\TestCase;

class SweepstakeTest extends TestCase
{
    /**
     * Regression to ensure sweepstakes are inactive when not activated.
     */
    public function testActiveIsFalseWhenNotSet()
    {
        $sweepstake = new \Entities\Sweepstake;
        $this->assertSame(false, $sweepstake->isActive());
    }

    /**
     * Regression test to ensure drawings are empty and return an empty array when new
     */
    public function testDrawingIsEmptyArrayWhenNotSet()
    {
        $sweepstake = new \Entities\Sweepstake;
        $this->assertSame([], $sweepstake->getDrawing());
    }
}

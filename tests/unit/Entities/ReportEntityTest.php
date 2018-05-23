<?php

use Entities\Report;
use PHPUnit\Framework\TestCase;

class ReportEntityTest extends TestCase
{
    public function testProgramGetterAndSetter()
    {
        $report = new Report();
        $this->assertSame(null,$report->getProgram());

        $report->setProgram('hello world');
        $this->assertSame('hello world', $report->getProgram());

        $report->setProgram('');
        $this->assertSame(null, $report->getProgram());
    }
}

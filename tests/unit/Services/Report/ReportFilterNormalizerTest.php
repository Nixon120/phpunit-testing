<?php

class ReportFilterNormalizerTest extends AbstractReportTest
{
    public function testGetIdFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getIdFilter(""));
        $this->assertEquals("`Report`.`id` LIKE ?", $inputNormalizer->getIdFilter(1));
    }

    public function testGetIdFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getIdFilterArgs(""));
        $this->assertEquals(['%1%'], $inputNormalizer->getIdFilterArgs(1));
    }

    public function testGetUserFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getUserFilter(""));
        $this->assertEquals("`Report`.`user` = ?", $inputNormalizer->getUserFilter(1));
    }

    public function testGetUserFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getUserFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getUserFilterArgs(1));
    }

    public function testGetProcessedFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getProcessedFilter(""));
        $this->assertEquals("`Report`.`processed` = ?", $inputNormalizer->getProcessedFilter(1));
    }

    public function testGetProcessedFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getProcessedFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getProcessedFilterArgs(1));
    }

    public function testGetReportFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getReportFilter(""));
        $this->assertEquals("`Report`.`report` = ?", $inputNormalizer->getReportFilter(1));
    }

    public function testGetReportFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getReportFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getReportFilterArgs(1));
    }

    public function testGetFormatFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getFormatFilter(""));
        $this->assertEquals("`Report`.`format` = ?", $inputNormalizer->getFormatFilter(1));
    }

    public function testGetFormatFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getFormatFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getFormatFilterArgs(1));
    }

    public function testGetOrganizationFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getOrganizationFilter(""));
        $this->assertEquals("`Report`.`organization` = ?", $inputNormalizer->getOrganizationFilter(1));
    }

    public function testGetOrganizationFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getOrganizationFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getOrganizationFilterArgs(1));
    }


    public function testGetProgramFilter()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertFalse($inputNormalizer->getProgramFilter(""));
        $this->assertEquals("`Report`.`program` = ?", $inputNormalizer->getProgramFilter(1));
    }

    public function testGetProgramFilterArgs()
    {
        $inputNormalizer = new \Services\Report\ReportFilterNormalizer([]);
        $this->assertEmpty($inputNormalizer->getProgramFilterArgs(""));
        $this->assertEquals(['1'], $inputNormalizer->getProgramFilterArgs(1));
    }
}

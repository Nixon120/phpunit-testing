<?php

namespace AllDigitalRewards\RewardStack\Services\Report;

class ReportDataResponse
{
    /**
     * @var array
     */
    private $reportData = [];

    /**
     * @var int
     */
    private $totalRecords;

    /**
     * @return array
     */
    public function getReportData()
    {
        return $this->reportData;
    }

    /**
     * @param array $reportData
     */
    public function setReportData($reportData)
    {
        $this->reportData = $reportData;
    }

    /**
     * @return int
     */
    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * @param int $totalRecords
     */
    public function setTotalRecords($totalRecords)
    {
        $this->totalRecords = $totalRecords;
    }

}
<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class ProgramSummary extends AbstractReport
{
    const NAME = 'Program Summary';

    const REPORT = 7;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.name as program_name' => 'Program Name',
            'Program.unique_id' => 'Program ID',
            'Program.start_date as `Program Start Date`' => 'Program Start Date',
            'Program.end_date as `Program End Date`' => 'Program End Date',
            'Program.grace_period as `Grace Period`' => 'Grace Period',
            'COUNT(DISTINCT Participant.id) as `Participant Count`' => 'Participant Count',
            'SUM(Adjustment.adjustment_total) as `Total Participant Points`' => 'Total Participant Points',
            'SUM(`Transaction`.transCount) as `Transaction Count`' => 'Transaction Count',
            'SUM(`Transaction`.transCount) as `Total Redemptions`' => 'Total Redemptions',
            'SUM(`Transaction`.transaction_total) as `Total Redemption Value`' => 'Total Redemption Value'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM Participant
LEFT JOIN Organization ON Organization.id = Participant.organization_id
LEFT JOIN Program ON Participant.program_id = Program.id
LEFT JOIN (
   SELECT participant_id, SUM(amount) AS adjustment_total
   FROM Adjustment 
   WHERE `type` = 1   
   GROUP BY participant_id   
   ) Adjustment ON Participant.id = Adjustment.participant_id
LEFT JOIN (
   SELECT participant_id, SUM(total) AS transaction_total, COUNT(id) AS transCount
   FROM `Transaction`
   GROUP BY participant_id
   ) `Transaction` ON Participant.id = `Transaction`.participant_id
WHERE 1=1 
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id, Program.name
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}


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
            'COUNT(Participant.id) as `Participant Count`' => 'Participant Count',
            'SUM(Adjustment.amount) as `Total Participant Points`' => 'Total Participant Points',
            'COUNT(`Transaction`.id) as `Transaction Count`' => 'Transaction Count',
            'COUNT(`Transaction`.id) as `Total Redemptions`' => 'Total Redemptions',
            'SUM(`Transaction`.total) as `Total Redemption Value`' => 'Total Redemption Value'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM Program
JOIN Organization ON Program.organization_id = Organization.id 
LEFT JOIN Participant ON Program.id = Participant.program_id
LEFT JOIN `Transaction` on Participant.id = `Transaction`.participant_id
LEFT JOIN Adjustment on `Transaction`.participant_id = Adjustment.participant_id
WHERE 1=1 AND Adjustment.type = 1 
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id, Program.name
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }


    public function getReportMetaFields(): array
    {
        try {
            $meta = [
                'participant' => $this->getAvailableMetaFields('participant')
            ];
        } catch (\Exception $e) {
            // Log failure
            $meta = [];
        }

        return $meta;
    }
}


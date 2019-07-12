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
            'COUNT(distinct Participant.id) as `Participant Count`' => 'Participant Count',
            'ROUND(IFNULL(SUM(Adjustment.amount) * Program.point, 0), 2) as `Total Participant Points`' => 'Total Participant Points',
            'COUNT(Transaction.id) as `Transaction Count`' => 'Transaction Count',
            'IFNULL(SUM(TransactionItem.quantity), 0) as `Total Redemptions`' => 'Total Redemptions',
            'IFNULL(SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping, 0) + IFNULL(TransactionProduct.handling, 0)) * TransactionItem.quantity)), 0) as `Total`' => 'Total Redemption Value'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM `Program`
JOIN `Organization` ON `Organization`.id = `Program`.organization_id 
LEFT JOIN `Participant` ON `Participant`.program_id = `Program`.id 
LEFT JOIN `Adjustment` ON `Adjustment`.participant_id = `Participant`.id AND `Adjustment`.type = 1
LEFT JOIN `Transaction` ON `Transaction`.participant_id = `Participant`.id
LEFT JOIN `TransactionItem` ON `TransactionItem`.transaction_id = `Transaction`.id
LEFT JOIN `TransactionProduct` ON `TransactionProduct`.reference_id = `TransactionItem`.reference_id
WHERE 1=1  
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

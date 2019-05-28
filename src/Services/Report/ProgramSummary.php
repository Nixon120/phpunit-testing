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
            'ParticipantSub.`Participant Count`' => 'Participant Count',
            'AdjustmentSub.`Total Particpant Points`' => 'Total Participant Points',
            'COUNT(distinct Transaction.id) as `Transaction Count`' => 'Transaction Count',
            'SUM(TransactionItem.quantity) as `Total Redemptions`' => 'Total Redemptions',
            'SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping,0) + IFNULL(TransactionProduct.handling,0)) * TransactionItem.quantity)) as `Total`' => 'Total Redemption Value'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $participantSub = $this->getParticipantSubquerySQL();
        $adjustmentSub = $this->getAdjustmentSubquerySQL();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM `TransactionItem`
{$participantSub}
{$adjustmentSub} 
JOIN `Transaction` ON `Transaction`.id = `TransactionItem`.transaction_id 
JOIN `TransactionProduct` ON `TransactionItem`.reference_id = `TransactionProduct`.reference_id 
JOIN `Participant` ON `Transaction`.participant_id = `Participant`.id 
JOIN `Program` ON `Program`.id = `Participant`.program_id 
JOIN `Organization` ON `Organization`.id = `Participant`.organization_id 
WHERE 1=1  
{$this->getFilter()->getFilterConditionSql()}
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

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
            '(SELECT COUNT(Participant.id) FROM Participant WHERE Participant.program_id = Program.id) as `Participant Count`' => 'Participant Count',
            'ROUND((SELECT IFNULL((SUM(Adjustment.amount) * Program.point), 0) FROM Adjustment WHERE Adjustment.participant_id IN ((SELECT Participant.id FROM Participant WHERE Participant.program_id = Program.id)) AND Adjustment.type = 1), 2) as `Total Participant Points`' => 'Total Participant Points',
            '(SELECT COUNT(Transaction.id) FROM Transaction WHERE Transaction.participant_id IN (SELECT Participant.id FROM Participant WHERE Participant.program_id = Program.id)) as `Transaction Count`' => 'Transaction Count',
            '(SELECT IFNULL(SUM(TransactionItem.quantity), 0) FROM TransactionItem LEFT JOIN Transaction ON TransactionItem.transaction_id = Transaction.id WHERE Transaction.participant_id IN ((SELECT Participant.id FROM Participant WHERE Participant.program_id = Program.id))) as `Total Redemptions`' => 'Total Redemptions',
            '(SELECT IFNULL(SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping, 0) + IFNULL(TransactionProduct.handling, 0))) * TransactionItem.quantity), 0) FROM Transaction LEFT JOIN TransactionItem ON TransactionItem.transaction_id = Transaction.id LEFT JOIN TransactionProduct ON TransactionProduct.reference_id = TransactionItem.reference_id WHERE Transaction.participant_id IN ((SELECT Participant.id FROM Participant WHERE Participant.program_id = Program.id))) as `Total`' => 'Total Redemption Value'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM `Program`
JOIN `Organization` ON `Organization`.id = `Program`.organization_id
WHERE 1=1  
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

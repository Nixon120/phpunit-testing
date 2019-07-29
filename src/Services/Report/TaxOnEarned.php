<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class TaxOnEarned extends AbstractReport
{
    const NAME = 'Tax';

    const REPORT = 8;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.name as program_name' => 'Program Name',
            'Program.unique_id as program_id' => 'Program ID',
            'Participant.unique_id' => 'Participant ID',
            'IF(Participant.active = 1, \'Active\', \'Inactive\') as status' => 'Status',
            'IFNULL(Participant.firstname, Address.firstname) as firstname' => 'First Name',
            'IFNULL(Participant.lastname, Address.lastname) as lastname' => 'Last Name',
            'Participant.birthdate' => 'Date of Birth',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.phone' => 'Phone',
            'Participant.email_address' => 'Email',
            'IFNULL((SELECT SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping, 0) + IFNULL(TransactionProduct.handling, 0)) * TransactionItem.quantity)) FROM `Transaction` JOIN `TransactionItem` ON `TransactionItem`.transaction_id = `Transaction`.id JOIN `TransactionProduct` ON `TransactionItem`.reference_id = `TransactionProduct`.reference_id WHERE `Transaction`.participant_id = Participant.id AND `Transaction`.`created_at` >= ? AND `Transaction`.`created_at` <= ?), 0) as `Award Amount`' => 'Award Amount',
            'ROUND(IFNULL((SELECT SUM(adjustment.amount) FROM adjustment WHERE adjustment.participant_id = participant.id AND adjustment.type = 1 AND adjustment.created_at >= ? AND adjustment.created_at <= ?), 0) - IFNULL((SELECT SUM(adjustment.amount) FROM adjustment WHERE adjustment.participant_id = participant.id AND adjustment.type = 2 AND adjustment.transaction_id IS NULL AND adjustment.created_at >= ? AND adjustment.created_at <= ?), 0), 2) AS `Earned Amount`' => 'Earned Amount',
        ]);
    }

    private function addPreparedColumnArgs(array &$args)
    {
        $date = new \DateTime;
        $startDate = $this->getFilter()->getInput()['start_date'];
        if (trim($startDate) === "" || $startDate === null) {
            $startDate = '2000-01-01 00:00:00';
        }

        $endDate = $this->getFilter()->getInput()['end_date'];
        if (trim($endDate) === "" || $endDate === null) {
            $endDate = $date->format('Y-m-d 23:59:59');
        }

        array_unshift($args, $startDate, $endDate);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $args = $this->getFilter()->getFilterConditionArgs();
        $this->addPreparedColumnArgs($args);

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection} FROM `Participant`
       JOIN `Program` ON `Program`.id = `Participant`.program_id
       JOIN `Organization` ON `Organization`.id = `Participant`.organization_id
       LEFT JOIN `Address` ON `Participant`.id = `Address`.participant_id
WHERE 1 = 1
  AND participant.id IN (SELECT DISTINCT Adjustment.participant_id
                         FROM Adjustment
                                LEFT JOIN Transaction ON Adjustment.transaction_id = Transaction.id
                                LEFT JOIN TransactionItem ON TransactionItem.transaction_id = Transaction.id
                                LEFT JOIN TransactionProduct ON transactionItem.reference_id = TransactionProduct.reference_id
WHERE 1=1 
SQL;

        // Fetch TAX EXEMPT skus from catalog
        $taxExemptSkus = $this->getFactory()->getCatalogService()->getTaxExemptSkus();
        if (!empty($taxExemptSkus)) {
            $args = array_merge($args, $taxExemptSkus);
            $placeholder = rtrim(str_repeat('?,', count($taxExemptSkus)), ',');
            $query .= " AND TransactionProduct.unique_id NOT IN ({$placeholder}) ";
        }

        $query .= " AND adjustment.created_at >= ? AND adjustment.created_at <= ?) ";
        $query .= $this->getFilter()->getFilterConditionSql();

        return $this->fetchDataForReport($query, $args);
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

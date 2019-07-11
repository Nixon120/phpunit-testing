<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class Tax extends AbstractReport
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
            'IFNULL(Address.firstname, Participant.firstname) as firstname' => 'First Name',
            'IFNULL(Address.lastname, Participant.lastname) as lastname' => 'Last Name',
            'Participant.birthdate' => 'Date of Birth',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.phone' => 'Phone',
            'Participant.email_address' => 'Email',
            'TransactionItem.quantity' => 'Transaction Count',
            'TransactionProduct.vendor_code' => 'Product SKU',
            'TransactionProduct.name' => 'Product Name',
            'SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping,0) + IFNULL(TransactionProduct.handling,0)) * TransactionItem.quantity)) as `Award Amount`' => 'Award Amount',
            '(SUM(((TransactionProduct.retail + IFNULL(TransactionProduct.shipping,0) + IFNULL(TransactionProduct.handling,0)) * TransactionItem.quantity)) * Program.point) as `Shipped Points Redeemed`' => 'Shipped Points Redeemed',
            'ROUND(IFNULL((SELECT SUM(adjustment.amount) FROM adjustment WHERE adjustment.participant_id = participant.id AND adjustment.type = 1 AND adjustment.created_at >= ? AND adjustment.created_at <= ?), 0) * Program.point, 2) AS `Points Earned`' => 'Points Earned'
        ]);
    }

    private function addPreparedColumnArgs(array &$args)
    {
        $date = new \DateTime;
        $startDate = $this->getFilter()->getInput()['start_date'];
        if(trim($startDate) === "" || $startDate === null) {
            $startDate = '2000-01-01 00:00:00';
        }

        $endDate = $this->getFilter()->getInput()['end_date'];
        if(trim($endDate) === "" || $endDate === null) {
            $endDate = $date->format('Y-m-d 23:59:59');
        }

        array_unshift($args, $startDate, $endDate);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();
        $args = $this->getFilter()->getFilterConditionArgs();

        if (strpos($selection, 'Points Earned') !== false) {
            $this->addPreparedColumnArgs($args);
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS {$selection} FROM `TransactionItem` "
            . "JOIN `Transaction` ON `Transaction`.id = `TransactionItem`.transaction_id "
            . "JOIN `TransactionProduct` ON `TransactionItem`.reference_id = `TransactionProduct`.reference_id "
            . "JOIN `Participant` ON `Transaction`.participant_id = `Participant`.id "
            . "JOIN `Program` ON `Program`.id = `Participant`.program_id "
            . "JOIN `Organization` ON `Organization`.id = `Participant`.organization_id "
            . "LEFT JOIN `Address` ON `Transaction`.shipping_reference = `Address`.reference_id "
            . "  AND Participant.id = Address.participant_id "
            . "WHERE 1=1 "
            . $this->getFilter()->getFilterConditionSql()
            . " GROUP BY `Participant`.unique_id";

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

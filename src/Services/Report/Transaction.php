<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class Transaction extends AbstractReport
{
    const NAME = 'Participant Transaction';

    const REPORT = 2;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.unique_id as program_uuid' => 'Program ID',
            'Program.name as program_name' => 'Program Name',
            'Adjustment.created_at' => 'Transaction Date',
            'Adjustment.id as `Adjustment ID`' => 'Adjustment ID',
            'Transaction.id as `Transaction ID`' => 'Transaction ID',
            "IF(Adjustment.type = 1, 'Credit', 'Debit') as `Transaction Type`" => 'Transaction Type',
            'Participant.unique_id' => 'Participant ID',
            'IF(Participant.active = 1, \'Active\', \'Inactive\') as status' => 'Status',
            'IFNULL(Address.firstname, Participant.firstname) as firstname' => 'First Name',
            'IFNULL(Address.lastname, Participant.lastname) as lastname' => 'Last Name',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.phone' => 'Phone',
            'Participant.birthdate' => 'Date of Birth',
            'Participant.email_address' => 'Email Address',
            'TransactionProduct.category as `Reward Type`' => 'Reward Type',
            'TransactionItem.quantity' => 'Transaction Count',
            'TransactionItem.reissue_date' => 'Reissue Date',
            'TransactionProduct.vendor_code' => 'Product SKU',
            'TransactionProduct.name' => 'Product Name',
            '((TransactionProduct.retail + IFNULL(TransactionProduct.shipping,0) + IFNULL(TransactionProduct.handling,0)) * TransactionItem.quantity) as Total' => 'Award Amount',
            '\'\' as shipping_reference' => 'Shipping Reference',
            'Program.unique_id as portal_name' => 'Portal Name',
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection} 
FROM `TransactionItem`
JOIN `Transaction` ON `Transaction`.id = `TransactionItem`.transaction_id
JOIN `TransactionProduct` ON `TransactionItem`.reference_id = `TransactionProduct`.reference_id
JOIN `Adjustment` ON `Adjustment`.transaction_id = Transaction.id
JOIN `Participant` ON `Transaction`.participant_id = `Participant`.id
JOIN `Program` ON `Program`.id = `Participant`.program_id 
JOIN `Organization` ON `Organization`.id = `Participant`.organization_id 
LEFT JOIN `Address` ON `Transaction`.shipping_reference = `Address`.reference_id 
  AND Participant.id = Address.participant_id 
WHERE 1=1
{$this->getFilter()->getFilterConditionSql()}
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }

    public function getReportMetaFields(): array
    {
        try {
            $meta = [
                'transaction' => $this->getAvailableMetaFields('transaction'),
                'participant' => $this->getAvailableMetaFields('participant')
            ];
        } catch (\Exception $e) {
            // Log failure
            $meta = [];
        }

        return $meta;
    }
}

<?php
namespace Services\Report;

class Transaction extends AbstractReport
{
    public $name = 'Participant Redemption';

    public function __construct(ServiceFactory $factory)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Program.unique_id as program_uuid' => 'Program UUID',
            'Program.name as program_name' => 'Program',
            'Adjustment.created_at' => 'Date',
            'Adjustment.id as `Adjustment ID`' => 'Adjustment ID',
            'Transaction.id as `Transaction ID`' => 'Transaction ID',
            "IF(Adjustment.type = 1, 'Credit', 'Debit') as `Transaction Type`" => 'Transaction Type',
            'Participant.unique_id' => 'Participant ID',
            'Address.firstname' => 'First Name',
            'Address.lastname' => 'Last Name',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.phone' => 'Phone',
            'Participant.email_address' => 'Email Address',
            'TransactionProduct.category as `Reward Type`' => 'Reward Type',
            'TransactionItem.quantity' => 'Item Qty', # Ordered quantity
            'TransactionProduct.vendor_code' => 'Item SKU', # Ordered SKU
            'TransactionProduct.name' => 'Item Description', # Item Description
            '((TransactionProduct.retail + IFNULL(TransactionProduct.shipping,0) + IFNULL(TransactionProduct.handling,0)) * TransactionItem.quantity) as Total' => 'Total'
        ]);
    }

    public function getReportData()
    {
        $selection = implode(', ', $this->getFields());
        $query = <<<SQL
SELECT {$selection} 
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
}
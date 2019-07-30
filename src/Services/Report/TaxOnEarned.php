<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class TaxOnEarned extends AbstractReport
{
    const NAME = 'TaxOnEarned';

    const REPORT = 8;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            '`organization`.`name`' => 'Organization Name',
            '`program`.`name`' => 'Program Name',
            '`program`.`unique_id`' => 'Program ID',
            '`participant`.`unique_id`' => 'Participant ID',
            'IF(`participant`.`active` = 1, \'Active\', \'Inactive\')' => 'Status',
            'IFNULL(`participant`.`firstname`, `Address`.`firstname`)' => 'First Name',
            'IFNULL(`participant`.`lastname`, `Address`.`lastname`)' => 'Last Name',
            '`participant`.`birthdate`' => 'Date of Birth',
            '`address`.`address1`' => 'Address1',
            '`address`.`address2`' => 'Address2',
            '`address`.`city`' => 'City',
            '`address`.`state`' => 'State',
            '`address`.`zip`' => 'Zip',
            '`participant`.`email_address`' => 'Email',
            '(SUM(IF(adjustment.type = 1, adjustment.amount, 0))-SUM(IF(adjustment.type = 2 AND adjustment.transaction_id IS NULL, adjustment.amount, 0)))' => 'Earned Amount',
            '(SUM(IF(adjustment.type = 2 AND adjustment.transaction_id IS NOT NULL, adjustment.amount, 0)))' => 'Redeemed Amount'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();
        $args = $this->getFilter()->getFilterConditionArgs();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM participant
LEFT JOIN `adjustment` ON `participant`.`id` = `adjustment`.`participant_id`
LEFT JOIN `address` ON `participant`.`address_reference` = `address`.`reference_id` AND `participant`.`id` = `address`.`participant_id`
LEFT JOIN `program` ON `participant`.program_id = `program`.id
LEFT JOIN `organization` ON `program`.organization_id = `organization`.id
LEFT JOIN `transaction` ON adjustment.transaction_id = `transaction`.id
LEFT JOIN `transactionitem` ON `transactionitem`.transaction_id = `transaction`.id
LEFT JOIN `transactionproduct` ON `transactionitem`.reference_id = `transactionproduct`.reference_id
WHERE 1=1
{$this->getFilter()->getFilterConditionSql()}
GROUP BY `Participant`.unique_id
SQL;

        // Fetch TAX EXEMPT skus from catalog
        $taxExemptSkus = $this->getFactory()->getCatalogService()->getTaxExemptSkus();
        if (!empty($taxExemptSkus)) {
            $args = array_merge($args, $taxExemptSkus);
            $placeholder = rtrim(str_repeat('?,', count($taxExemptSkus)), ',');
            $query .= " AND `transactionproduct`.unique_id NOT IN ({$placeholder}) ";
        }

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

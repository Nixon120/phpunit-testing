<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class TaxOnEarned extends AbstractReport
{
    const NAME = 'TaxOnEarned';

    const REPORT = 10;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            '`organization`.`name` as `Organization Name`' => 'Organization Name',
            '`program`.`name` as `Program Name`' => 'Program Name',
            '`program`.`unique_id` as `Program ID`' => 'Program ID',
            '`participant`.`unique_id` as `Participant ID`' => 'Participant ID',
            'IF(`participant`.`active` = 1, \'Active\', \'Inactive\') as `Status`' => 'Status',
            'IFNULL(`participant`.`firstname`, `Address`.`firstname`) as `First Name`' => 'First Name',
            'IFNULL(`participant`.`lastname`, `Address`.`lastname`) as `Last Name`' => 'Last Name',
            '`participant`.`birthdate` as `Date of Birth`' => 'Date of Birth',
            '`address`.`address1` as `Address1`' => 'Address1',
            '`address`.`address2` as `Address2`' => 'Address2',
            '`address`.`city` as `City`' => 'City',
            '`address`.`state` as `State`' => 'State',
            '`address`.`zip` as `Zip`' => 'Zip',
            '`participant`.`email_address` as `Email`' => 'Email',
            'ROUND(IFNULL((SELECT SUM(a.amount) FROM adjustment a WHERE a.participant_id = adjustment.participant_id AND a.`type` = 1 AND a.`created_at` >= ? AND a.`created_at` <= ?),0) - IFNULL((SELECT SUM(a.amount) FROM adjustment a WHERE a.participant_id = adjustment.participant_id AND a.`type` = 2 AND a.`transaction_id` IS NULL AND a.`created_at` >= ? AND a.`created_at` <= ?),0), 2) as `Earned Amount`' => 'Earned Amount',
            'ROUND(IFNULL((SELECT SUM(a.amount) FROM adjustment a WHERE a.participant_id = adjustment.participant_id AND a.`type` = 2 AND a.transaction_id IS NOT NULL AND a.transaction_id NOT IN (SELECT DISTINCT transaction_id FROM `transactionitem` LEFT JOIN `transactionproduct` ON `transactionitem`.reference_id = `transactionproduct`.reference_id WHERE 1=1 {taxExemptPlaceholder}) AND a.`created_at` >= ? AND a.`created_at` <= ?),0), 2) as `Redeemed Amount`' => 'Redeemed Amount'
        ]);
    }

    private function addPreparedColumnArgsDateBetween(array &$args)
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

        $args = array_merge($args, [$startDate, $endDate]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();
        $args = [];

        if (strpos($selection, 'Earned Amount') !== false) {
            // we run twice, because there are two date ranges
            $this->addPreparedColumnArgsDateBetween($args);
            $this->addPreparedColumnArgsDateBetween($args);
        }

        if (strpos($selection, '{taxExemptPlaceholder}') !== false) {
            $taxExemptSkus = $this->getFactory()->getCatalogService()->getTaxExemptSkus();
            $replace = '';
            if(!empty($taxExemptSkus)) {
                $replace = $this->getTaxExemptSql($args, $taxExemptSkus);
            }

            $selection = str_replace('{taxExemptPlaceholder}', $replace, $selection);
        }

        if (strpos($selection, 'Redeemed Amount') !== false) {
            $this->addPreparedColumnArgsDateBetween($args);
        }

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM participant
LEFT JOIN `adjustment` ON `participant`.`id` = `adjustment`.`participant_id`
LEFT JOIN `address` ON `participant`.`address_reference` = `address`.`reference_id` AND `participant`.`id` = `address`.`participant_id`
LEFT JOIN `program` ON `participant`.program_id = `program`.id
LEFT JOIN `organization` ON `program`.organization_id = `organization`.id
WHERE 1=1
AND `adjustment`.`type` IN (1,2)
{$this->getFilter()->getFilterConditionSql()}
GROUP BY `Participant`.unique_id
SQL;
        $args = array_merge($args, $this->getFilter()->getFilterConditionArgs());

        return $this->fetchDataForReport($query, $args);
    }

    /**
     * @param array $args
     * @param array $taxExemptSkus
     * @return string
     */
    private function getTaxExemptSql(array &$args, array $taxExemptSkus)
    {
        $args = array_merge($args, $taxExemptSkus);
        $placeholder = rtrim(str_repeat('?,', count($taxExemptSkus)), ',');
        return " AND `transactionproduct`.unique_id IN ({$placeholder}) ";
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

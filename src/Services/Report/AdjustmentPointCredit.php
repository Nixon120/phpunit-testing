<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class AdjustmentPointCredit extends AbstractReport
{
    const NAME = 'Adjustment Point Credit';

    const REPORT = 9;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Program.name as program_name' => 'Program Name',
            'Program.unique_id as program_id' => 'Program ID',
            'Participant.unique_id' => 'Participant ID',
            'Participant.email_address' => 'Email',
            'IFNULL(Address.firstname, Participant.firstname) as firstname' => 'First Name',
            'IFNULL(Address.lastname, Participant.lastname) as lastname' => 'Last Name',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Adjustment.description' => 'Description',
            'Adjustment.amount' => 'Points Earned',
            'Program.start_date' => 'Program Start Date',
            'Program.end_date' => 'Program End Date',
            'Adjustment.completed_at' => 'Incentive Completed Date',
            'Adjustment.created_at' => 'Adjustment Created On'
        ]);
    }


    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM `Adjustment`
JOIN `Participant` ON `Adjustment`.participant_id = `Participant`.id 
JOIN `Program` ON `Program`.id = `Participant`.program_id 
LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id AND `Participant`.id = `Address`.participant_id 
WHERE 1=1 
AND Adjustment.type = 1 
{$this->getFilter()->getFilterConditionSql()}
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

<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class Enrollment extends AbstractReport
{
    const NAME = 'Participant Enrollment';

    const REPORT = 1;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        //@TODO Company Country, Company name? Maybe?
        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.unique_id as program_id' => 'Program ID',
            'Program.name as program_name' => 'Program Name',
            'Program.start_date as `Program Start Date`' => 'Program Start Date',
            'Program.end_date as `Program End Date`' => 'Program End Date',
            'Participant.created_at' => 'Registration Date',
            'Participant.unique_id' => 'Participant ID',
            'IF(Participant.active = 1, \'Active\', \'Inactive\') as status' => 'Status',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
            'Participant.birthdate' => 'Date of Birth',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.phone' => 'Phone',
            'Participant.email_address' => 'Email',
            "CASE Participant.active WHEN 1 THEN 'active' ELSE 'inactive' END" => 'Status'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection} FROM `Participant` 
JOIN `Organization` ON Organization.id = `Participant`.organization_id
JOIN `Program` ON `Program`.id = `Participant`.program_id
LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id
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
                'participant' => $this->getAvailableMetaFields('participant')
            ];
        } catch (\Exception $e) {
            // Log failure
            $meta = [];
        }

        return $meta;
    }
}

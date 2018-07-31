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
            'Participant.created_at' => 'Registration Date',
            'Participant.unique_id' => 'Participant ID',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
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
        $organization = $this->getFilter()->getInput()['organization'] ?? null;
        $program = $this->getFilter()->getInput()['program'] ?? null;
        $args = [];

        $query = <<<SQL
SELECT DISTINCT `key` FROM `ParticipantMeta` 
JOIN `Participant` ON `ParticipantMeta`.participant_id = `Participant`.id 
JOIN `Organization` ON Organization.id = `Participant`.organization_id 
JOIN `Program` ON `Program`.id = `Participant`.program_id 
LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id 
  AND Participant.id = Address.participant_id 
WHERE 1=1
SQL;

        if($organization !== null && $organization !== '') {
            $query .= " AND `Organization`.`unique_id` = ?";
            $args[] = $organization;
        }

        if($program !== null && $program !== '') {
            $query .= " AND `Program`.`unique_id` = ?";
            $args[] = $program;
        }

        return $this->fetchMetaForReport($query, $args);
    }
}

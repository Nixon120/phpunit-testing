<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class Sweepstake extends AbstractReport
{
    const NAME = 'Sweepstake Drawings';

    const REPORT = 6;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.unique_id as program_id' => 'Program ID',
            'Program.name as program_name' => 'Program Name',
            'Participant.unique_id' => 'Participant ID',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
            'Participant.email_address' => 'Email',
            'Participant.birthdate' => 'Date of Birth',
            'IF(Participant.active = 1, \'Active\', \'Inactive\') as status' => 'Status',
            'Address.zip' => 'Zip Code',
            'SweepstakeEntry.created_at' => 'Entered Date/Time'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection} 
FROM SweepstakeEntry
LEFT JOIN Participant ON SweepstakeEntry.participant_id = Participant.id
LEFT JOIN `Organization` ON `Organization`.id = `Participant`.organization_id
LEFT JOIN `Program` ON `Program`.id = `Participant`.program_id
LEFT JOIN `Address` ON `Address`.reference_id  = `Participant`.address_reference
WHERE 1=1
  {$this->getFilter()->getFilterConditionSql()}
ORDER BY SweepstakeEntry.created_at DESC
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

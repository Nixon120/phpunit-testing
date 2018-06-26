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
            'Participant.unique_id' => 'Participant ID',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
            'Participant.email_address' => 'Email',
            'Participant.birthdate' => 'Date of Birth',
            'Address.zip' => 'Zip Code',
            'SweepstakeEntry.created_at' => 'Entered Date/Time',
            'IF(SweepstakeEntry.sweepstake_draw_id IS NOT NULL,\'Yes\',\'No\')' => 'Winner'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection} 
FROM SweepstakeEntry
JOIN Participant ON SweepstakeEntry.participant_id = Participant.id
JOIN `Organization` ON `Organization`.id = `Participant`.organization_id
JOIN `Program` ON `Program`.id = `Participant`.program_id
JOIN `Address` ON `Address`.reference_id  = `Participant`.address_reference
WHERE 1=1
  {$this->getFilter()->getFilterConditionSql()}
ORDER BY SweepstakeEntry.created_at DESC
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

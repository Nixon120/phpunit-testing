<?php
namespace Services\Report;

class Sweepstake extends AbstractReport
{
    public $name = 'Sweepstake Drawings';

    public function __construct(ServiceFactory $factory)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Participant.email_address' => 'Email',
            'Participant.unique_id' => 'Unique ID',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
            'SweepstakeEntry.created_at' => 'Entered',
            'IF(SweepstakeEntry.sweepstake_draw_id IS NOT NULL,\'Yes\',\'No\')' => 'Winner'
        ]);
    }

    public function getReportData()
    {
        $selection = implode(', ', $this->getFields());

        $query = <<<SQL
SELECT {$selection} 
FROM SweepstakeEntry
JOIN Participant ON SweepstakeEntry.participant_id = Participant.id
JOIN `Organization` ON `Organization`.id = `Participant`.organization_id
JOIN `Program` ON `Program`.id = `Participant`.program_id
WHERE 1=1
  {$this->getFilter()->getFilterConditionSql()}
ORDER BY SweepstakeEntry.created_at DESC
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

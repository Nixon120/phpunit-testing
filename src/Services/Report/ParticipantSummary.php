<?php
namespace Services\Report;

class ParticipantSummary extends AbstractReport
{
    public $name = 'Participant Summary';

    public function __construct(ServiceFactory $factory)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Program.unique_id' => 'Program ID',
            'Program.name' => 'Program Name',
            'MIN(Participant.created_at) as `Enroll Start Date`' => 'Enroll Start Date',
            'MAX(Participant.created_at) as `Enroll End Date`' => 'Enroll End Date',
            'COUNT(Participant.id) as `Participant Count`' => 'Participant Count'
        ]);
    }

    public function getReportData()
    {
        $selection = implode(', ', $this->getFields());

        $query = <<<SQL
SELECT {$selection}
FROM `Program`
JOIN Organization ON Program.organization_id = Organization.id
LEFT JOIN Participant ON Program.id = Participant.program_id
WHERE 1=1
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id, Program.name
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

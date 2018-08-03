<?php

namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class ParticipantSummary extends AbstractReport
{
    const NAME = 'Participant Summary';

    const REPORT = 4;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.unique_id' => 'Program ID',
            'Program.name' => 'Program Name',
            'MIN(Participant.created_at) as `Enroll Start Date`' => 'Enroll Start Date',
            'MAX(Participant.created_at) as `Enroll End Date`' => 'Enroll End Date',
            'COUNT(Participant.id) as `Participant Count`' => 'Participant Count'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS {$selection}
FROM `Program`
JOIN Organization ON Program.organization_id = Organization.id
LEFT JOIN Participant ON Program.id = Participant.program_id
WHERE 1=1
{$this->getFilter()->getFilterConditionSql()}
GROUP BY Program.unique_id, Program.name
SQL;

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }


    public function getReportMetaFields(): array
    {
        try {
            $meta = [
                'participant' => $this->getMetaFields('participant')
            ];
        } catch (\Exception $e) {
            // Log failure
            $meta = [];
        }

        return $meta;
    }
}

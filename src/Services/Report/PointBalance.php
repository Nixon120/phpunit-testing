<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;

class PointBalance extends AbstractReport
{
    const NAME = 'Participant Point Balance';

    const REPORT = 5;

    public function __construct(?ServiceFactory $factory = null)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Organization.name as organization_name' => 'Organization Name',
            'Program.unique_id as program_id' => 'Program ID',
            'Program.name as program_name' => 'Program Name',
            'Participant.email_address' => 'Email',
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
            'ROUND(SUM(CASE When `Adjustment`.type=1 Then `Adjustment`.amount Else 0 End ) - SUM(CASE When `Adjustment`.type=2 Then `Adjustment`.amount Else 0 End ), 2) as `Current Balance`' => 'Current Balance'
        ]);
    }

    public function getReportData(): ReportDataResponse
    {
        $selection = implode(', ', $this->getFields());
        $selection .= $this->getMetaSelectionSql();

        $query = "SELECT SQL_CALC_FOUND_ROWS {$selection} FROM `Adjustment` "
            . "JOIN `Participant` ON `Participant`.id = `Adjustment`.participant_id "
            . "JOIN `Program` ON `Program`.id = `Participant`.program_id "
            . "JOIN `Organization` ON `Organization`.id = `Participant`.organization_id "
            . "LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id "
            . "  AND Participant.id = Address.participant_id "
            . "WHERE 1=1 "
            . $this->getFilter()->getFilterConditionSql()
            . ' GROUP BY `Adjustment`.participant_id';

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

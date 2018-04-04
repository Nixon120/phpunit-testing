<?php
namespace Services\Report;

class PointBalance extends AbstractReport
{
    public $name = 'Participant Point Balance';

    public function __construct(ServiceFactory $factory)
    {
        parent::__construct($factory);

        $this->setFieldMap([
            'Participant.email_address' => 'Email',
            'Participant.unique_id' => 'Unique ID',
            'Participant.firstname' => 'First Name',
            'Participant.lastname' => 'Last Name',
            'Address.address1' => 'Address1',
            'Address.address2' => 'Address2',
            'Address.city' => 'City',
            'Address.state' => 'State',
            'Address.zip' => 'Zip',
            'Participant.credit' => 'Point'
        ]);
    }

    public function getReportData()
    {
        $selection = implode(', ', $this->getFields());

        $query = "SELECT {$selection} FROM `Participant` "
            . "JOIN `Program` ON `Program`.id = `Participant`.program_id "
            . "JOIN `Organization` ON `Organization`.id = `Participant`.organization_id "
            . "LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id "
            . "  AND Participant.id = Address.participant_id "
            . "WHERE 1=1 "
            . $this->getFilter()->getFilterConditionSql();

        return $this->fetchDataForReport($query, $this->getFilter()->getFilterConditionArgs());
    }
}

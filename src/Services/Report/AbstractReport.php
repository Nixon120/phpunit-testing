<?php
namespace Services\Report;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;
use Controllers\Report\InputNormalizer;
use Entities\Event;
use Entities\Report;
use Services\Interfaces as Interfaces;
use Services\Report\Interfaces\Reportable;

abstract class AbstractReport implements Reportable
{
    const RESULT_COUNT = 100;

    const NAME = 'Unknown';

    const REPORT = 0;

    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var InputNormalizer
     */
    public $input;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $requestedMetaFields = [];

    /**
     * offset for html reports
     *
     * @var int
     */
    private $offset = 0;

    /**
     * current page for html reports
     *
     * @var int
     */
    private $page = 1;

    /**
     * @var Interfaces\FilterNormalizer
     */
    private $filter;

    /**
     * @var array
     */
    private $fieldMap;

    /**
     * @var bool
     */
    private $limitResultCount = true;

    public function __construct(?ServiceFactory $factory = null)
    {
        if ($factory !== null) {
            $this->setFactory($factory);
        }
    }

    /**
     * @return ServiceFactory
     */
    public function getFactory(): ServiceFactory
    {
        return $this->factory;
    }

    /**
     * @param ServiceFactory $factory
     */
    public function setFactory(ServiceFactory $factory): void
    {
        $this->factory = $factory;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getRequestedMetaFields(): array
    {
        return $this->requestedMetaFields;
    }

    /**
     * @param array $meta
     */
    public function setRequestedMetaFields(array $meta): void
    {
        $this->requestedMetaFields = $meta;
    }

    /**
     * @param Interfaces\FilterNormalizer $filter
     */
    public function setFilter(Interfaces\FilterNormalizer $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return Interfaces\FilterNormalizer
     */
    public function getFilter(): Interfaces\FilterNormalizer
    {
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /**
     * @param array $map
     */
    public function setFieldMap(array $map)
    {
        $this->fieldMap = $map;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }

    /**
     * @return bool
     */
    public function isLimitResultCount(): bool
    {
        return $this->limitResultCount;
    }

    /**
     * @param bool $limitResultCount
     */
    public function setLimitResultCount(bool $limitResultCount): void
    {
        $this->limitResultCount = $limitResultCount;
    }

    /**
     * @return string
     */
    public function getReportName(): string
    {
        return static::NAME;
    }

    /**
     * @return string
     */
    public function getReportClassification(): string
    {
        return static::REPORT;
    }

    /**
     * @param $query
     * @param $args
     * @return array
     */
    protected function fetchDataForReport($query, $args): ReportDataResponse
    {
        $reportData = new ReportDataResponse();

        if ($this->isLimitResultCount()) {
            $query .= " LIMIT " . self::RESULT_COUNT . " OFFSET " . $this->offset;
        }

        $sth = $this->getFactory()->getDatabase()->prepare($query);
        $sth->execute($args);

        $reportData->setReportData($sth->fetchAll());
        $reportData->setTotalRecords($this->getFoundRows());

        return $reportData;
    }

    /**
     * @param $query
     * @param $args
     * @return array
     */
    protected function fetchMetaForReport($query, $args): array
    {
        $sth = $this->getFactory()->getDatabase()->prepare($query);
        $sth->execute($args);

        $fields = $sth->fetchAll();

        if (empty($fields)) {
            return [];
        }

        return $this->normalizeMetaFields($fields);
    }

    private function normalizeMetaFields(array $fields): array
    {
        $container = [];

        foreach ($fields as $field) {
            array_push($container, $field['key']);
        }

        return $container;
    }

    /**
     * @param InputNormalizer $input
     * @return bool
     */
    public function setInputNormalizer(InputNormalizer $input)
    {
        try {
            $this->input = $input;
            $this->mapFieldsFromInput();
            $this->mapMetaFromInput();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return InputNormalizer
     */
    public function getInputNormalizer(): InputNormalizer
    {
        return $this->input;
    }

    /**
     * @return array
     */
    public function getReportMetaFields(): array
    {
        return [];
    }

    /**
     * @return ReportDataResponse
     */
    public function getReportData(): ReportDataResponse
    {
        return new ReportDataResponse();
    }

    /**
     * @throws \Exception
     */
    public function mapFieldsFromInput()
    {
        $fields = $this->getInputNormalizer()->getRequestedFields();
        $fieldContainer = [];
        $map = $this->getFieldMap();
        foreach ($fields as $field) {
            if (isset($map[$field]) === false) {
                throw new \Exception('Unknown field request');
            }
            $fieldContainer[] = $field;
        }
        $this->setFields($fieldContainer);
    }

    /**
     * @throws \Exception
     */
    public function mapMetaFromInput()
    {
        $metadata = $this->getInputNormalizer()->getMetaFields();
        $metaContainer = [];

        foreach ($metadata as $type => $meta) {
            $metaContainer[$type] = $meta;
        }

        $this->setRequestedMetaFields($metaContainer);
    }

    /**
     * @return array
     */
    public function getReportHeaders(): array
    {
        $headers = [];
        $map = $this->getFieldMap();
        foreach ($this->getFields() as $field) {
            $headers[] = $map[$field];
        }

        foreach ($this->getRequestedMetaFields() as $type => $typeHeaders) {
            //Participant is available on basically every report
            //Transaction is only available on two reports so far.
            if ($type === 'transaction' && in_array($this->getReportClassification(), [2, 3]) === false) {
                continue;
            }

            $available = $this->getAvailableMetaFields($type);
            foreach ($typeHeaders as $head) {
                //is this a legit meta field ?
                if (in_array($head, $available)) {
                    $headers[] = ucfirst($head);
                }
            }
        }

        return $headers;
    }

    public function getMetaSelectionSql(): string
    {
        return $this->getParticipantMetaSelectionSql() . $this->getTransactionMetaSelectionSql();
    }

    private function getParticipantMetaSelectionSql(): string
    {
        $meta = $this->getRequestedMetaFields();
        $sql = '';

        if (!empty($meta['participant'])) {
            $sql .= ',';
            foreach ($meta['participant'] as $key) {
                $sql .= <<<SQL
(
SELECT `value` 
FROM `ParticipantMeta` meta 
WHERE `key` = '{$key}'  -- pass key via arg
  AND meta.participant_id = Participant.id
) as `{$key}`
SQL;
                $sql .= ',';
            }
        }


        return rtrim($sql, ',');
    }

    private function getTransactionMetaSelectionSql(): string
    {
        $meta = $this->getRequestedMetaFields();
        $sql = '';

        if (!empty($meta['transaction']) && in_array($this->getReportClassification(), [2, 3]) === true) {
            //@TODO MAKE SURE META MATCHES AVAILABLE REPORT META..
            //or else, room for SQL injection.
            $sql .= ',';
            foreach ($meta['transaction'] as $key) {
                $sql .= <<<SQL
(
SELECT `value` 
FROM `TransactionMeta` meta 
WHERE `key` = '{$key}'  -- pass key via arg 
  AND meta.transaction_id = Transaction.id
) as {$key}
SQL;
                $sql .= ',';
            }
        }

        return rtrim($sql, ',');
    }

    /**
     * @return Report
     */
    public function request(): Report
    {
        $report = new Report;
        $date = (new \DateTime)->format('Y-m-d H:i:s');
        $organization = $this->getInputNormalizer()->getInput()['organization'] ?? null;
        $program = $this->getInputNormalizer()->getInput()['program'] ?? null;
        $user = $this->getInputNormalizer()->getInput()['user'] ?? null;
        $format = $this->getInputNormalizer()->getInput()['report_format'] ?? 'csv';

        $report->setOrganization($organization);
        $report->setProgram($program);
        $report->setUser($user);
        $report->setReport($this->getReportClassification());
        $report->setFormat($format);
        $report->setProcessed(0);
        $report->setParameters(json_encode($this->getInputNormalizer()->getInput()));
        $report->setCreatedAt($date);
        $report->setUpdatedAt($date);

        $repository = $this->getFactory()->getReportRepository();
        $repository->place($report);
        $entity = $repository->getReportById($repository->getLastInsertId());
        $this->queueReportEvent($entity);
        return $entity;
    }

    /**
     * @param Report $report
     */
    private function queueReportEvent(Report $report)
    {
        $event = new Event;
        $event->setName('Report.request');
        $event->setEntityId($report->getId());
        $this->getFactory()
            ->getEventPublisher()
            ->publish(json_encode($event));
    }

    private function getFoundRows(): int
    {
        $query = "SELECT FOUND_ROWS()";
        $sth = $this->getFactory()->getDatabase()->prepare($query);
        $sth->execute([]);
        return $sth->fetchColumn();
    }

    private function getTransactionMetaKeySql()
    {
        return <<<SQL
SELECT DISTINCT `key` FROM `TransactionMeta` 
JOIN `Transaction` ON `Transaction`.id = `TransactionMeta`.transaction_id 
JOIN `Participant` ON `Transaction`.participant_id = `Participant`.id 
JOIN `Program` ON `Program`.id = `Participant`.program_id 
JOIN `Organization` ON `Organization`.id = `Participant`.organization_id 
WHERE 1=1 
SQL;
    }

    private function getParticipantMetaKeySql()
    {
        return <<<SQL
SELECT DISTINCT `key` FROM `ParticipantMeta` 
JOIN `Participant` ON `ParticipantMeta`.participant_id = `Participant`.id 
JOIN `Organization` ON Organization.id = `Participant`.organization_id 
JOIN `Program` ON `Program`.id = `Participant`.program_id 
LEFT JOIN `Address` ON `Participant`.address_reference = `Address`.reference_id 
  AND Participant.id = Address.participant_id 
WHERE 1=1
SQL;
    }

    /**
     * @param $set
     * @return array
     * @throws \Exception
     */
    public function getAvailableMetaFields($set): array
    {
        $organization = $this->getFilter()->getInput()['organization'] ?? null;
        $program = $this->getFilter()->getInput()['program'] ?? null;
        $args = [];

        switch ($set) {
            case 'participant':
                $query = $this->getParticipantMetaKeySql();
                break;
            case 'transaction':
                $query = $this->getTransactionMetaKeySql();
                break;
            default:
                throw new \Exception('Invalid meta specification on field key query');
        }

        if ($organization !== null && $organization !== '') {
            $query .= " AND `Organization`.`unique_id` = ?";
            $args[] = $organization;
        }

        if ($program !== null && $program !== '') {
            $query .= " AND `Program`.`unique_id` = ?";
            $args[] = $program;
        }

        return $this->fetchMetaForReport($query, $args);
    }

    public function getParticipantSubquerySQL()
    {
        $program = $this->getFilter()->getInput()['program'] ?? null;
        $startDate = $this->getFilter()->getInput()['start_date'] ?? null;
        $endDate = $this->getFilter()->getInput()['end_date'] ?? null;
        $where = '';
        $db = $this->getFactory()->getDatabase();

        if ($program !== null && $program != '') {
            $where .= " AND program_id = (SELECT id FROM program WHERE unique_id = ".$db->quote($program).")";
        }

        if ($startDate !== null && $startDate != '') {
            $where .= " AND created_at >= ".$db->quote($startDate);
        }

        if ($endDate !== null && $endDate != '') {
            $where .= " AND created_at <= ".$db->quote($endDate);
        }

        $query = <<<SQL
JOIN (
  SELECT COUNT(*) AS 'Participant Count' FROM participant 
  WHERE 1=1 
  {$where}
) ParticipantSub
SQL;

        return $query;
    }

    public function getAdjustmentSubquerySQL()
    {
        $program = $this->getFilter()->getInput()['program'] ?? null;
        $startDate = $this->getFilter()->getInput()['start_date'] ?? null;
        $endDate = $this->getFilter()->getInput()['end_date'] ?? null;
        $where = '';
        $db = $this->getFactory()->getDatabase();

        if ($program !== null && $program != '') {
            $where .= " AND participant_id IN (SELECT id FROM participant WHERE program_id = (SELECT id FROM program WHERE unique_id = ".$db->quote($program)."))";
        }

        if ($startDate !== null && $startDate != '') {
            $startDate .= ' 00:00:00';
            $where .= " AND created_at >= ".$db->quote($startDate);
        }

        if ($endDate !== null && $endDate != '') {
            $endDate .= ' 23:59:59';
            $where .= " AND created_at <= ".$db->quote($endDate);
        }

        $query = <<<SQL
JOIN (
  SELECT SUM(amount) AS 'Total Particpant Points' FROM adjustment 
  WHERE 1=1   
   AND `type` = 1
   {$where}
) AdjustmentSub
SQL;

        return $query;
    }
}

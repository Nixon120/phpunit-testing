<?php
namespace Services\Report;

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
    protected function fetchDataForReport($query, $args)
    {
        if ($this->isLimitResultCount()) {
            $query .= " LIMIT " . self::RESULT_COUNT . " OFFSET " . $this->offset;
        }

        $sth = $this->getFactory()->getDatabase()->prepare($query);
        $sth->execute($args);
        return $sth->fetchAll();
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
    public function getReportData(): array
    {
        return [];
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
     * @return array
     */
    public function getReportHeaders(): array
    {
        $headers = [];
        $map = $this->getFieldMap();
        foreach ($this->getFields() as $field) {
            $headers[] = $map[$field];
        }

        return $headers;
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
        $format = $this->getInputNormalizer()->getInput()['report_format'] ?? 'csv';

        $report->setOrganization($organization);
        $report->setProgram($program);
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

    /**
     * @param $query
     * @param $args
     * @return int
     */
    protected function fetchRecordCount($query, $args): int
    {
        $sth = $this->getFactory()->getDatabase()->prepare($query);
        $sth->execute($args);
        return $sth->fetchColumn();
    }
}

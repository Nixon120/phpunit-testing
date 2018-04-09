<?php

namespace Services\Report;

use Controllers\Report\InputNormalizer;
use Services\Interfaces as Interfaces;
use Services\Report\Interfaces\Reportable;

abstract class AbstractReport implements Reportable
{
    const RESULT_COUNT = 100;

    /**
     * @var ServiceFactory
     */
    private $factory;

    public $name = 'Unknown';

    /**
     * @var array
     */
    private $fields = [];

    private $offset = 0;

    private $page = 1;

    private $filter;

    private $fieldMap;

    /**
     * @var bool
     */
    private $limitResultCount = true;

    public function __construct(ServiceFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getReportName(): string
    {
        return $this->name;
    }

    protected function fetchDataForReport($query, $args)
    {
        if ($this->limitResultCount === true) {
            $query .= " LIMIT " . self::RESULT_COUNT . " OFFSET " . $this->offset;
        }

        $sth = $this->factory->getDatabase()->prepare($query);
        $sth->execute($args);

        return $sth->fetchAll();
    }

    protected function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    protected function getFields(): array
    {
        return $this->fields;
    }

    public function setFilter(Interfaces\FilterNormalizer $filter)
    {
        $this->filter = $filter;
    }

    public function getFilter(): Interfaces\FilterNormalizer
    {
        return $this->filter;
    }

    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    public function setFieldMap(array $map)
    {
        $this->fieldMap = $map;
    }

    public function setInput(InputNormalizer $input)
    {
        $fields = $input->getRequestedFields();
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

    public function getReportHeaders(): array
    {
        $headers = [];
        $map = $this->getFieldMap();
        foreach ($this->getFields() as $field) {
            $headers[] = $map[$field];
        }

        return $headers;
    }

    public function export()
    {
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Disposition: attachment; filename="' . $this->getReportName() . '.csv"');
        header('Content-Type: text/csv');

        $output = fopen('php://output', 'a');

        fputcsv(
            $output,
            $this->getReportHeaders()
        );

        $this->limitResultCount = false;

        foreach ($this->getReportData() as $row) {
            fputcsv($output, $row);
        }
    }
}

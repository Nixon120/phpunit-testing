<?php

namespace Services\Report\Interfaces;

use Controllers\Report\InputNormalizer;
use Services\Interfaces\FilterNormalizer;

interface Reportable
{

    public function getReportName(): string;

    public function setInput(InputNormalizer $input);

    public function getReportData();

    public function getReportHeaders();

    public function setFilter(FilterNormalizer $filter);

    public function getFilter(): FilterNormalizer;

    public function getPage(): int;

    public function setFieldMap(array $map);

    public function getFieldMap(): array;

    public function export();
}

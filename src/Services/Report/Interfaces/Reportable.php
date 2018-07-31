<?php

namespace Services\Report\Interfaces;

use AllDigitalRewards\RewardStack\Services\Report\ReportDataResponse;
use Controllers\Report\InputNormalizer;
use Services\Interfaces\FilterNormalizer;

interface Reportable
{

    public function getReportName(): string;

    public function setInputNormalizer(InputNormalizer $input);

    public function getReportData(): ReportDataResponse;

    public function getReportMetaFields(): array;

    public function getReportHeaders();

    public function setFilter(FilterNormalizer $filter);

    public function getFilter(): FilterNormalizer;

    public function setPage(int $page);

    public function getPage(): int;

    public function setOffset(int $offset);

    public function getOffset(): int;

    public function setFieldMap(array $map);

    public function getFieldMap(): array;

    public function request();
}

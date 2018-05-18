<?php
namespace Repositories;

use Entities\Report;
use Services\Report\AbstractReport;
use Services\Report\ReportFilterNormalizer;

class ReportRepository extends BaseRepository
{
    protected $table = 'Report';

    public function getRepositoryEntity()
    {
        return Report::class;
    }

    public function getCollectionQuery(): string
    {
        $this->orderBy = ' ORDER BY id DESC';
        $where = " WHERE 1 = 1 ";
        if (!empty($this->getOrganizationIdContainer())) {
            $organizationIdString = implode(',', $this->getOrganizationIdContainer());
            $where = <<<SQL
WHERE Organization.organization_id IN ({$organizationIdString})
SQL;
        }

        return <<<SQL
SELECT Report.*
FROM Report
LEFT JOIN Organization ON Organization.unique_id = Report.organization
{$where}
SQL;
    }

    public function getReportList(ReportFilterNormalizer $filters, $offset, $limit)
    {
        /** @var Report[] $reports */
        $reports = $this->getCollection($filters, $offset, $limit);
        $container = [];
        if (!empty($reports)) {
            foreach ($reports as $key => $report) {
                $container[$key] = $report->toArray();
                $container[$key]['parameters'] = json_decode($container[$key]['parameters'], true);
                $container[$key]['report'] = $report->getReportName();
            }
        }

        return $container;
    }

    public function getReportById($id): ?Report
    {
        $sql = <<<SQL
SELECT Report.* 
FROM `Report` 
WHERE `Report`.id = ?
SQL;

        $args = [$id];
        if (!$report = $this->query($sql, $args, Report::class)) {
            return null;
        }

        return $report;
    }
}

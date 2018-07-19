<?php

namespace Services\Report;

use Services\AbstractFilterNormalizer;

class ReportFilterNormalizer extends AbstractFilterNormalizer
{
    public function getIdFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`id` LIKE ?";
        }

        return false;
    }

    public function getIdFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = '%'.$value.'%';
        }

        return $args;
    }

    public function getUserFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`user` = ?";
        }

        return false;
    }

    public function getUserFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getProcessedFilter($value)
    {
        $string = "";

        if ($value !== "") {
            $string = "`Report`.`processed` = ?";
        }

        return $string;
    }

    public function getProcessedFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getReportFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`report` = ?";
        }

        return false;
    }

    public function getReportFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getFormatFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`format` = ?";
        }

        return false;
    }

    public function getFormatFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getOrganizationFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`organization` = ?";
        }

        return false;
    }

    public function getOrganizationFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }

    public function getProgramFilter($value)
    {
        if ($value !== "") {
            return "`Report`.`program` = ?";
        }

        return false;
    }

    public function getProgramFilterArgs($value)
    {
        $args = [];

        if ($value !== "") {
            $args[] = $value;
        }

        return $args;
    }
}

<?php
use \Controllers\Report as Controllers;

$app->group('/report', function () use ($app, $createRoute, $updateRoute) {

    $this->get('/organization/list', function ($request, $response) {
        $organization = new \Controllers\Report\Organization(
            $request,
            $response,
            $this->get('report')
        );
        return $organization->renderListResult();
    });

    $this->get('/program/list', function ($request, $response) {
        $organization = new \Controllers\Report\Program(
            $request,
            $response,
            $this->get('report')
        );
        return $organization->renderListResult();
    });
    $app->get('', function ($request, $response) {
        $report = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('report'));
        return $report->renderReport();
    });
});
$app->group('/api/report', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $report = new Controllers\JsonView($request, $response, $this->get('report'));
        return $report->reportData();
    });
});

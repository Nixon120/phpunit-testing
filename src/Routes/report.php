<?php
use \Controllers\Report as Controllers;

$app->group('/api/report', function () use ($app, $createRoute, $updateRoute) {
    $app->get('/meta', Controllers\RequestReportMetaFields::class);
    $app->get('/data', Controllers\RequestReport::class);
    $app->get('/publish_sftp/{id}', Controllers\RequestReport::class);
    $app->get('', Controllers\ReportList::class);
});

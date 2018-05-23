<?php
use \Controllers\Report as Controllers;

$app->group('/api/report', function () use ($app, $createRoute, $updateRoute) {
    $app->get('/data', Controllers\RequestReport::class);
    $app->get('', Controllers\ReportList::class);
});

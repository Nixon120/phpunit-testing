<?php
use \Controllers\Sftp as Controllers;

$app->group('/api/sftp', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', Controllers\SftpList::class);
    $app->get('/publish_sftp/{id}', Controllers\PublishReportSftp::class);
});
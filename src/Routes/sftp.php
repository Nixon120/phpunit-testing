<?php
use \Controllers\Sftp as Controllers;

$app->group('/api/sftp', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', Controllers\SftpList::class);
    $app->get('/{id}', Controllers\SftpSingle::class);
    $app->put('/{id}', Controllers\SftpUpdate::class);
    $app->delete('/{id}', Controllers\SftpDelete::class);
    $app->post('', Controllers\SftpCreate::class);
});
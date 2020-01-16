#!/usr/bin/env php
<?php

use AllDigitalRewards\RewardStack\Services\Program\ProgramImageFileTypeFixer;

/**
 * @var \Psr\Container\ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";

$fixerService = new ProgramImageFileTypeFixer;

/** @var PDO $db */
$db = $container->get('database');

$sql = "SELECT id, program_id, priority FROM `LayoutRow`";
$sth = $db->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll();
if (!$rows) {
    exit(0);
}
try {
    foreach ($rows as $row) {
        $sql = "SELECT priority, row_id, image  FROM `LayoutRowCard` WHERE row_id = {$row['id']}";
        $sth = $db->prepare($sql);
        $sth->execute();
        $cardRow = $sth->fetch();
        if (empty($cardRow['image']) === false && $fixerService->getImageType($cardRow['image']) === 'Array') {
            $imagePath = $row['program_id'] . $row['priority'] . $cardRow['priority'];
            $imageName = $fixerService->resaveCorruptedImageFile($imagePath, $cardRow['image']);
            $sql = "UPDATE `LayoutRowCard` SET image = ?, updated_at = NOW() WHERE row_id = {$row['id']}";
            $sth = $db->prepare($sql);
            $sth->execute([$imageName]);
        }
    }
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

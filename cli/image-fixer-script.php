#!/usr/bin/env php
<?php
use AllDigitalRewards\RewardStack\Services\Program\ProgramImageFileTypeFixer;
use Psr\Container\ContainerInterface;

//324cdd53610a7f0214d82b0cbc20eb228ae67f67.Array
//77cfa314c5194c3d41a8d22fcbf327d6f90a96b4.Array
//dfdbb033770d22bc9cba86a2f1d3ce473922b6fb.Array

/**
 * @var ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";

/** @var PDO $pdo */
$pdo = $container->get('database');
$fixerService = new ProgramImageFileTypeFixer;

$sql = "SELECT lc.id, l.program_id, l.priority as row_priority, lc.priority, row_id, image
FROM layoutrowcard lc
LEFT JOIN layoutrow l ON lc.row_id = l.id
WHERE lc.image LIKE '%.Array' 
  AND l.program_id IS NOT NULL";

$sth = $pdo->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll();
if (!$rows) {
    exit(0);
}
try {
    foreach ($rows as $row) {
        $imagePath = $row['program_id'] . $row['row_priority'] . $row['priority'];
        $imageName = $fixerService->resaveCorruptedImageFile($imagePath, $row['image']);
        $sql = "UPDATE `LayoutRowCard` SET image = ?, updated_at = NOW() WHERE id = {$row['id']}";
        $sth = $pdo->prepare($sql);
        $sth->execute([$imageName]);
    }
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

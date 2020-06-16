#!/usr/bin/env php
<?php
use Psr\Container\ContainerInterface;

/**
 * @var ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";

/** @var PDO $pdo */
$pdo = $container->get('database');

$sql = <<<SQL
SELECT 
   `participant`.`id`,
   `participant`.`created_at`
FROM `participant`
WHERE `participant`.`created_at` >= '2017-01-01 00:00:00'
SQL;

$sth = $pdo->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll();
try {
    foreach ($rows as $row) {
        $sql = <<<SQL
INSERT INTO participant_change_log (action, logged_at, participant_id, data, username)
VALUES ('create', ?, ?, '{"status": "active"}', 'system')
SQL;
        $sth = $pdo->prepare($sql);
        $sth->execute([$row['created_at'], $row['id']]);
    }
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

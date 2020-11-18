#!/usr/bin/env php
<?php
use Psr\Container\ContainerInterface;
use AllDigitalRewards\StatusEnum\StatusEnum;

/**
 * @var ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";

/** @var PDO $pdo */
$pdo = $container->get('database');

$totalRecords = 0;
$page = 1;
$rows = changeLogSql($pdo, $page);
$totalRecords += count($rows);
while (empty($rows) === false) {
    try {
        insertStatus($pdo, $rows);
        $page++;
        $rows = changeLogSql($pdo, $page);
        $totalRecords += count($rows);
    } catch (\Exception $exception) {
        echo $exception->getMessage();
        exit(1);
    }
}

echo 'done processing ' . $totalRecords . ' records';

function changeLogSql(PDO $pdo, $page)
{
    $offset = $page * 1000;
    $changeLogSql = <<<SQL
SELECT participant_id,
CASE WHEN `status` = 'active' THEN 1
    WHEN `status` = 'hold' THEN 2
    WHEN `status` = 'inactive' THEN 3
    END `status`,
logged_at
FROM participant_change_log
LIMIT ?
OFFSET ?
SQL;

    $sth = $pdo->prepare($changeLogSql);
    $sth->execute([1000, $offset]);
    return $sth->fetchAll();
}

function insertStatus(PDO $pdo, $rows)
{
    $args = [];
    foreach ($rows as $row) {
        $args = array_merge($args, array_values($row));
    }

    $sql = "INSERT INTO participant_status (participant_id, status, created_at) VALUES ";
    $count = count($rows);
    for ($i = 1; $i <= $count; $i++) {
        if ($count === $i) {
            $sql .= '(?,?,?)';
        } else {
            $sql .= '(?,?,?),';
        }
    }
    $sth = $pdo->prepare($sql);
    $sth->execute($args);
}

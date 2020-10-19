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

$rows = changeLogSql($pdo);
while (empty($rows) === false) {
    try {
        insertStatus($pdo, $rows);
        $offset = count($rows) + 1;
        $rows = changeLogSql($pdo, $offset);
    } catch (\Exception $exception) {
        echo $exception->getMessage();
        exit(1);
    }
}

function changeLogSql(PDO $pdo, $offset = 0)
{
    $limit = 1000;
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
    $sth->execute([$limit, $offset]);
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

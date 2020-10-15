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
        foreach ($rows as $row) {
            insertStatus($pdo, $row);
        }
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
SELECT *
FROM `participant_change_log`
LIMIT ?
OFFSET ?
SQL;

    $sth = $pdo->prepare($changeLogSql);
    $sth->execute([$limit, $offset]);
    return $sth->fetchAll();
}

function insertStatus(PDO $pdo, $row)
{
    $sql = <<<SQL
    INSERT INTO participant_status (participant_id, status, created_at)
    VALUES (?,?,?)
SQL;
    $sth = $pdo->prepare($sql);
    $statusEnum = new AllDigitalRewards\StatusEnum\StatusEnum();
    $status = $statusEnum->hydrateStatus($row['status']);
    $sth->execute([$row['participant_id'], $status, $row['logged_at']]);
}

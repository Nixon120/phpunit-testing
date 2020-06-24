#!/usr/bin/env php
<?php
use AllDigitalRewards\RewardStack\Services\Program\ProgramImageFileTypeFixer;
use Psr\Container\ContainerInterface;

/**
 * @var ContainerInterface $container
 */
require __DIR__ . "/../cli-bootstrap.php";

/** @var PDO $pdo */
$pdo = $container->get('database');
$fixerService = new ProgramImageFileTypeFixer;

$sql = <<<SQL
SELECT participant_change_log.participant_id as id,
       IF(
                   JSON_UNQUOTE(JSON_EXTRACT(data, '$.is_frozen')) = 'frozen',
                   'hold',
                   IF(`Participant`.`active` = 1, 'active', 'inactive')
           ) as `status`
FROM `Participant`
         JOIN participant_change_log on participant_change_log.participant_id = `Participant`.id
    AND participant_change_log.logged_at = (
        SELECT MAX(t2.logged_at)
        FROM participant_change_log t2
        WHERE t2.participant_id = participant_change_log.participant_id
    )
SQL;

$sth = $pdo->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll();
if (!$rows) {
    exit(0);
}
try {
    foreach ($rows as $row) {
        $sql = "UPDATE `participant_change_log` SET `participant_change_log`.`status` = ? WHERE `participant_change_log`.`participant_id` = ?";
        $sth = $pdo->prepare($sql);
        $sth->execute([$row['status'], $row['id']]);
    }
} catch (\Exception $exception) {
    echo $exception->getMessage();
}

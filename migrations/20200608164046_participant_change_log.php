<?php


use Phinx\Migration\AbstractMigration;

class ParticipantChangeLog extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
CREATE TABLE participant_change_log (
    id INT AUTO_INCREMENT NOT NULL,
    action VARCHAR(8) NOT NULL,
    logged_at DATETIME NOT NULL,
    object_id VARCHAR(64) DEFAULT NULL,
    data TEXT DEFAULT NULL,
    username VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id)) 
    DEFAULT CHARACTER SET utf8 
    COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->execute($sql);
    }
}

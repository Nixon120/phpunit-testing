<?php


use Phinx\Migration\AbstractMigration;

class ParticipantStatusTable extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
CREATE TABLE participant_status (
    `id` INT AUTO_INCREMENT NOT NULL,
    `participant_id` INT NOT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `updated_at` datetime(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
    `created_at` datetime(0) NULL DEFAULT current_timestamp(0),
    FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE,
    INDEX `status`(`status`) USING BTREE,
    INDEX `created_at`(`created_at`) USING BTREE,
    PRIMARY KEY(id)) 
    DEFAULT CHARACTER SET utf8 
    COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->execute($sql);
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class AddStatusToParticipantChangeLog extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE participant_change_log 
ADD COLUMN `status` VARCHAR(10) NOT NULL
SQL;
        $this->execute($sql);
    }
}

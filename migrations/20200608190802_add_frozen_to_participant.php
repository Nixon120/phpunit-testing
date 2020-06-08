<?php


use Phinx\Migration\AbstractMigration;

class AddFrozenToParticipant extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE participant 
ADD COLUMN `frozen` tinyint(1) DEFAULT 0
SQL;
        $this->execute($sql);
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class RemoveFrozenColumnFromParticipant extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE participant 
DROP COLUMN `frozen`
SQL;
        $this->execute($sql);
    }
}

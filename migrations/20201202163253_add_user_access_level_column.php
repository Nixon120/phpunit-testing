<?php


use Phinx\Migration\AbstractMigration;

class AddUserAccessLevelColumn extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE user 
ADD COLUMN `access_level` int DEFAULT 1
SQL;
        $this->execute($sql);
    }
}

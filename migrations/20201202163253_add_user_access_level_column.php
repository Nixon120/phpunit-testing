<?php


use Phinx\Migration\AbstractMigration;

class AddUserAccessLevelColumn extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE user 
ADD COLUMN `access_level` int DEFAULT 2
SQL;
        $this->execute($sql);
    }
}

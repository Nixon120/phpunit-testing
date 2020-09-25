<?php


use Phinx\Migration\AbstractMigration;

class AddActivityToAdjustment extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE adjustment 
ADD COLUMN `activity` VARCHAR(255)
SQL;
        $this->execute($sql);
    }
}

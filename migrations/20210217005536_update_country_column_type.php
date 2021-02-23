<?php


use Phinx\Migration\AbstractMigration;

class UpdateCountryColumnType extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE address 
MODIFY `country` VARCHAR(3)
SQL;
        $this->execute($sql);

        $sql = <<<SQL
ALTER TABLE address 
MODIFY `state` VARCHAR(255)
SQL;
        $this->execute($sql);
    }
}

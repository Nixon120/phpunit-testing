<?php


use Phinx\Migration\AbstractMigration;

class AddPasswordUpdatedAtColumn extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE user 
ADD COLUMN `password_updated_at` datetime(0) NULL DEFAULT current_timestamp(0)
SQL;
        $this->execute($sql);
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class AddIndustryProgramColumnToOrg extends AbstractMigration
{
    public function change()
    {
        $sql = <<<SQL
ALTER TABLE organization 
ADD COLUMN `industry_program` INT NULL
SQL;
        $this->execute($sql);
    }
}

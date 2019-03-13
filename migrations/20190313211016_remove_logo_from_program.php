<?php


use Phinx\Migration\AbstractMigration;

class RemoveLogoFromProgram extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Program');
        $table->removeColumn('logo')
            ->save();
    }
}

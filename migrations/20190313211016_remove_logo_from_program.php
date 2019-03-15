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

    public function down()
    {
        $table = $this->table('Program');
        $table->addColumn('logo', 'blob')
            ->save();
    }
}

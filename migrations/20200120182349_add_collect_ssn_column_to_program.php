<?php


use Phinx\Migration\AbstractMigration;

class AddCollectSsnColumnToProgram extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Program');
        $table->addColumn('collect_ssn', 'integer',['default' => 0, 'null' => false])->save();
    }
}

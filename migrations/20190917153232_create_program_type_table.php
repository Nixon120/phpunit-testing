<?php


use Phinx\Migration\AbstractMigration;

class CreateProgramTypeTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('ProgramType');
        $table->addColumn('name', 'string', ['null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('actions', 'json', ['null' => false])
            ->addIndex(['name'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}

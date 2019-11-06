<?php


use Phinx\Migration\AbstractMigration;

class CreateProgramToProgramTypeTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('ProgramToProgramType');
        $table->addColumn('program_id', 'integer', ['null' => false])
            ->addColumn('program_type_id', 'integer', ['null' => false])
            ->addForeignKey('program_id', 'Program', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('program_type_id', 'ProgramType', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}

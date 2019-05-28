<?php


use Phinx\Migration\AbstractMigration;

class RemoveFaqsFromProgram extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Faqs');
        $table->drop();
    }

    public function down()
    {
        $table = $this->table('Faqs');
        $table
            ->addColumn('program_id', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('question', 'text')
            ->addColumn('answer', 'text')
            ->addTimestamps()
            ->addIndex(['program_id'])
            ->create();
    }
}

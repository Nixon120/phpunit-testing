<?php

use Phinx\Migration\AbstractMigration;

class AddFaqsTable extends AbstractMigration
{
    public function change()
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

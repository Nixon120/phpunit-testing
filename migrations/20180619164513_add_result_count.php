<?php

use Phinx\Migration\AbstractMigration;

class AddResultCount extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Report');
        $table
            ->addColumn('result_count', 'integer', ['default' => 0, 'null' => false])
            ->update();
    }
}

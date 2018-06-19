<?php

use Phinx\Migration\AbstractMigration;

class AddReportCount extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Report');
        $table
            ->addColumn('report_count', 'integer', ['after' => 'parameters', 'null' => false])
            ->update();
    }
}

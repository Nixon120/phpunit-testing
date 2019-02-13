<?php


use Phinx\Migration\AbstractMigration;

class AddReportDateField extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Report');
        $table->addColumn('report_date', 'date', ['null' => true])
              ->save();
    }
}

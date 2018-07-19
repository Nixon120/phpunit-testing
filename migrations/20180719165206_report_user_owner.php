<?php


use Phinx\Migration\AbstractMigration;

class ReportUserOwner extends AbstractMigration
{
    public function change()
    {
        $report = $this->table('Report');

        $report->addColumn('user', 'string', ['null' => true])
            ->addIndex(['user'])
            ->save();
    }
}

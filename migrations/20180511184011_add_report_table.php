<?php


use Phinx\Migration\AbstractMigration;

class AddReportTable extends AbstractMigration
{
    public function change()
    {
        $reportOptionComment = <<<EOT
1 = Enrollment,
2 = Transaction,
3 = Redemption,
4 = Participant Summary,
5 = Point Balance,
6 = Sweepstake Drawing
EOT;

        $table = $this->table('Report');
        $table
            ->addColumn('organization', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('program', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('report', 'integer', ['limit' => 1, 'null' => false, 'comment' => $reportOptionComment])
            ->addColumn('format', 'string', ['null' => false])
            ->addColumn('attachment', 'string', ['null' => true])
            ->addColumn('processed', 'boolean', ['default' => 0])
            ->addColumn('parameters', 'text')
            ->addTimestamps()
            ->addIndex(['report'])
            ->addIndex(['processed'])
            ->addIndex(['organization'])
            ->addIndex(['program'])
            ->create();
    }
}

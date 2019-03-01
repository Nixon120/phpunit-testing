<?php


use Phinx\Migration\AbstractMigration;

class AddOfflineRedemptionTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('OfflineRedemption');
        $table
            ->addColumn('program_id', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('skus', 'text')
            ->addColumn('active', 'integer', ['limit' => 1])
            ->addTimestamps()
            ->addIndex(['program_id'])
            ->create();
    }
}

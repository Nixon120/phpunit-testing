<?php


use Phinx\Migration\AbstractMigration;

class CreateOneTimeAutoRedemption extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('OneTimeAutoRedemption');
        $table
            ->addColumn('program_id', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('product_sku', 'string', ['limit' => 45])
            ->addColumn('all_participant', 'integer', ['limit' => 1])
            ->addColumn('active', 'integer', ['limit' => 1])
            ->addColumn('redemption_date', 'date')
            ->addTimestamps()
            ->addIndex(['program_id'])
            ->create();
    }
}

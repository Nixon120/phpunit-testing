<?php


use Phinx\Migration\AbstractMigration;

class AddTransactionItemIdToAdjustmentTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Adjustment');
        $table->addColumn('transaction_item_id', 'integer', ['null' => true])
            ->addIndex(['transaction_item_id'])
            ->save();
    }
}

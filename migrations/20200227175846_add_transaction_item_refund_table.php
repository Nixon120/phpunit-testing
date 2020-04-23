<?php


use Phinx\Migration\AbstractMigration;

class AddTransactionItemRefundTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('transaction_item_refund');
        $table->addColumn('transaction_id', 'integer', ['null' => false])
            ->addColumn('transaction_item_id', 'integer', ['null' => false])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('complete', 'integer', ['length' => 1, 'default' => 0, 'null' => false])
            ->addForeignKey('transaction_id', 'Transaction', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('transaction_item_id', 'TransactionItem', 'id', ['delete' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $table->save();
    }
}

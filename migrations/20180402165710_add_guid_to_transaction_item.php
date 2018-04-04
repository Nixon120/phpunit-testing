<?php


use Phinx\Migration\AbstractMigration;

class AddGuidToTransactionItem extends AbstractMigration
{
    public function change()
    {
        $transactionItem = $this->table('TransactionItem');

        $transactionItem->addColumn('guid', 'string', ['limit' => 36, 'null' => true])
            ->addIndex(['guid'], ['unique' => true])
            ->save();
    }
}

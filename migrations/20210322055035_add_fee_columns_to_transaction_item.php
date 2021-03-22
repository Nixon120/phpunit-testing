<?php


use Phinx\Migration\AbstractMigration;

class AddFeeColumnsToTransactionItem extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('TransactionItem');
        $table->addColumn('program_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
            ->addColumn('var_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
            ->save();
    }
}

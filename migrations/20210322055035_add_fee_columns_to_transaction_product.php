<?php


use Phinx\Migration\AbstractMigration;

class AddFeeColumnsToTransactionProduct extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('TransactionProduct');
        $table->addColumn('program_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
            ->addColumn('var_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
            ->save();
    }
}

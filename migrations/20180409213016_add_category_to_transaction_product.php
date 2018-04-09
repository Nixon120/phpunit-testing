<?php

use Phinx\Migration\AbstractMigration;

class AddCategoryToTransactionProduct extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('TransactionProduct');
        $table->addColumn('category', 'string', ['null' => true])
            ->save();
    }
}

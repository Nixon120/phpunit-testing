<?php

use Phinx\Migration\AbstractMigration;

class AddReissueDateToTransactionItem extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('TransactionItem');
        $table->addColumn('reissue_date', 'date', ['null' => true])
            ->save();
    }
}

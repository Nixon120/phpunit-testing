<?php

use Phinx\Migration\AbstractMigration;

class RenameRefundToReturn extends AbstractMigration
{
    public function change()
    {
        $this->table('transaction_item_refund')
            ->rename('transaction_item_return')
            ->save();
    }
}

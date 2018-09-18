<?php


use Phinx\Migration\AbstractMigration;

class TransactionProductTermsAndDescriptionChange extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('transactionproduct');
        $table
            ->changeColumn('terms', 'text', ['null' => true])
            ->changeColumn('description', 'text', ['null' => true])
            ->save();
    }
}

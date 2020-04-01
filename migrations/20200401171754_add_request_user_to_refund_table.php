<?php


use Phinx\Migration\AbstractMigration;

class AddRequestUserToRefundTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('transaction_item_refund');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addForeignKey('user_id', 'user', 'id', [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION'
            ]);

        $table->save();
    }
}

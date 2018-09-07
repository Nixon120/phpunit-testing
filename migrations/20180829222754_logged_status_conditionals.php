<?php


use Phinx\Migration\AbstractMigration;

class LoggedStatusConditionals extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table
            ->addColumn('card_show', 'integer',['default' => 1, 'null' => false])
            ->save();
    }
}

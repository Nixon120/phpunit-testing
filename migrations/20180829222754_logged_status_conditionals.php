<?php


use Phinx\Migration\AbstractMigration;

class LoggedStatusConditionals extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table
            ->addColumn('card_show', 'integer',['default' => 2, 'null' => false])
            ->addColumn('text_markdown', 'text', ['null' => true])
            ->save();
    }
}

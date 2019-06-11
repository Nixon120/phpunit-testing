<?php


use Phinx\Migration\AbstractMigration;

class AddTitleToLayoutRowCards extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table->addColumn('title', 'string', ['null' => true])
              ->save();
    }
}

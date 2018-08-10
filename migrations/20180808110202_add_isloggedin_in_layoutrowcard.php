<?php


use Phinx\Migration\AbstractMigration;

class AddIsLoggedinInLayoutRowCard extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table->addColumn('is_logged_in', 'int',['default' => 0, 'null' => false])
            ->save();
    }
}

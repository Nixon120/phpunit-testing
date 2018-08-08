<?php


use Phinx\Migration\AbstractMigration;

class AddIsLoggedinInLayoutRowCard extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table->addColumn('isloggedin', 'int',['default' => 0, 'null' => false])
            ->save();
    }
}

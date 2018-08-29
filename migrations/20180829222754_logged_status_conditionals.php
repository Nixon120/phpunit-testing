<?php


use Phinx\Migration\AbstractMigration;

class LoggedStatusConditionals extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table
            ->addColumn('is_logged_out', 'integer',['default' => 0, 'null' => false])
            ->addColumn('logged_in_or_out', 'integer',['default' => 0, 'null' => false])
            ->save();
    }
}

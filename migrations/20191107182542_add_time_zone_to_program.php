<?php

use Phinx\Migration\AbstractMigration;

class AddTimeZoneToProgram extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Program');
        $table->addColumn('timezone', 'string', [
            'length' => 45,
            'null' => false,
            'default' => 'America/Phoenix'
        ])->save();
    }
}

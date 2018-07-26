<?php


use Phinx\Migration\AbstractMigration;

class AddProgramLowLevelDeposit extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Program');

        $table->addColumn('low_level_deposit', 'integer', ['null' => true])
            ->save();
    }
}

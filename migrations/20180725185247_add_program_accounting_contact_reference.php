<?php


use Phinx\Migration\AbstractMigration;

class AddProgramAccountingContactReference extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Program');

        $table->addColumn('accounting_contact_reference', 'string', ['null' => true])
            ->save();
    }
}

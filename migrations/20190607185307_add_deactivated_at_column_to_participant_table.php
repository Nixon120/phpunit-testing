<?php


use Phinx\Migration\AbstractMigration;

class AddDeactivatedAtColumnToParticipantTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Participant');
        $table->addColumn('deactivated_at', 'datetime', ['null' => true])
            ->save();
    }
}

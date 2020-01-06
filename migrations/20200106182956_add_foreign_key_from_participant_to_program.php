<?php


use Phinx\Migration\AbstractMigration;

class AddForeignKeyFromParticipantToProgram extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Participant');
        $table->addForeignKey('program_id', 'program', 'id',
            [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION'
            ])
            ->save();
    }
}

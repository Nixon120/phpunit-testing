<?php


use Phinx\Migration\AbstractMigration;

class AddForeignKeyFromParticipantMetaToParticipant extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('ParticipantMeta');
        $table->addForeignKey('participant_id', 'participant', 'id',
            [
                'delete'=> 'NO_ACTION',
                'update'=> 'NO_ACTION'
            ])
            ->save();
    }
}

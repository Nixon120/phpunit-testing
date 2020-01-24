<?php


use Phinx\Migration\AbstractMigration;

class ChangeParticipantMetaUpdatedAtDefaultTimestamp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('ParticipantMeta');
        $table->changeColumn('updated_at', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'null' => false,
            'update' => 'CURRENT_TIMESTAMP'
        ])->save();
    }

    public function down()
    {
        $table = $this->table('ParticipantMeta');
        $table->changeColumn('updated_at', 'datetime', [
            'default' => '0000-00-00 00:00:00',
            'null' => false,
            'update' => 'CURRENT_TIMESTAMP'
        ])->save();
    }
}

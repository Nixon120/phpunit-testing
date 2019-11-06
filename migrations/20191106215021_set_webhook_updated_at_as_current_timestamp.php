<?php


use Phinx\Migration\AbstractMigration;

class SetWebhookUpdatedAtAsCurrentTimestamp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Webhook');
        $table->changeColumn('updated_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->save();
    }

    public function down()
    {
        $table = $this->table('Webhook');
        $table->changeColumn('updated_at', 'datetime', ['null' => true])
            ->save();
    }
}

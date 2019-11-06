<?php


use Phinx\Migration\AbstractMigration;

class SetWebhookUpdatedAtAsCurrentTimestamp extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Webhook');
        $table->changeColumn('updated_at', 'date', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->save();
    }
}

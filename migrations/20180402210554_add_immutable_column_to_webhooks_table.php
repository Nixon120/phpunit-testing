<?php


use Phinx\Migration\AbstractMigration;

class AddImmutableColumnToWebhooksTable extends AbstractMigration
{
    public function change()
    {
        $webhook = $this->table('webhook');
        $webhook->addColumn('immutable', 'integer', ['limit' => 1, 'default' => 0])
        ->update();
    }
}

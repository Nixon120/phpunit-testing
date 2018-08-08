<?php


use Phinx\Migration\AbstractMigration;

class AddTextMarkDownInLayoutRowCard extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('layoutrowcard');
        $table->addColumn('text_markdown', 'text', ['null' => true])
            ->save();
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class AdjustmentReferenceUpdateToText extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Adjustment');
        $table
            ->changeColumn('reference', 'text', ['null' => true])
            ->update();
    }
}

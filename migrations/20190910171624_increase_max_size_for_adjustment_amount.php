<?php

use Phinx\Migration\AbstractMigration;

class IncreaseMaxSizeForAdjustmentAmount extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Adjustment');
        $table
            ->changeColumn(
                'amount',
                'decimal',
                [
                    'null' => false,
                    'precision' => 15,
                    'scale' => 5,
                ]
            )->update();
    }
}

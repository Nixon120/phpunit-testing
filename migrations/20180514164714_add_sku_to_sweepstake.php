<?php


use Phinx\Migration\AbstractMigration;

class AddSkuToSweepstake extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('sweepstake');
        $table->addColumn(
            'sku',
            'string',
            [
                'length' => 45,
                'default' => 'SWEEP01'
            ]
        )->save();
    }
}

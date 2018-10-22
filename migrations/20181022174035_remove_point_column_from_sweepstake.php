<?php


use Phinx\Migration\AbstractMigration;

class RemovePointColumnFromSweepstake extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Sweepstake');
        $table->removeColumn('point')
            ->save();
    }

    public function down()
    {
        $table = $this->table('Sweepstake');
        $table->addColumn('point', 'integer', ['null' => false])
            ->save();
    }
}

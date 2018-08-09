<?php


use Phinx\Migration\AbstractMigration;

class AddAltWinningEntries extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Sweepstakedraw');
        $table->addColumn('alt_entry', 'integer',
            ['after' => 'draw_count', 'null' => false, 'default' => 0])
            ->save();

        $table = $this->table('Sweepstakeentry');
        $table->addColumn('sweepstake_alt_draw_id', 'integer',
            ['after' => 'sweepstake_draw_id', 'null' => true ])
            ->save();
    }
}

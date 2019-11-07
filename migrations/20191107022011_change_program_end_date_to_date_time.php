<?php


use Phinx\Migration\AbstractMigration;

class ChangeProgramEndDateToDateTime extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('program');
        $table->changeColumn('end_date', 'datetime', ['null' => true])
            ->save();
    }

    public function down()
    {
        $table = $this->table('program');
        $table->changeColumn('end_date', 'date', ['null' => true])
            ->save();
    }
}

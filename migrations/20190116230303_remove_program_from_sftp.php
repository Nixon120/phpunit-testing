<?php


use Phinx\Migration\AbstractMigration;

class RemoveProgramFromSftp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Sftp');
        $table->removeColumn('program')
            ->save();
    }
}

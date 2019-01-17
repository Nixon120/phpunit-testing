<?php


use Phinx\Migration\AbstractMigration;

class RemoveProgramFromSftp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('Sftp');
        $table->removeColumn('program')
            ->addIndex(['file_path'], ['unique' => true])
            ->save();
    }
}

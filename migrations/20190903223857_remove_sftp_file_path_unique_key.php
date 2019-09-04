<?php


use Phinx\Migration\AbstractMigration;

class RemoveSftpFilePathUniqueKey extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('sftp');
        $table->removeIndex(['file_path']);
    }

    public function down()
    {
        $table = $this->table('sftp');
        $table->addIndex(['file_path'], ['unique' => true]);
    }
}

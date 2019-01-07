<?php


use Phinx\Migration\AbstractMigration;

class AddSftpTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Sftp');
        $table
            ->addColumn('program', 'string')
            ->addColumn('host', 'string')
            ->addColumn('port', 'string')
            ->addColumn('file_path', 'string')
            ->addColumn('username', 'string')
            ->addColumn('password', 'string')
            ->addColumn('key', 'text')
            ->addTimestamps()
            ->create();
    }
}

<?php

use Phinx\Migration\AbstractMigration;

class AddUserToSftp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('sftp');
        $table->addColumn('user_id', 'integer')
            ->addForeignKey('user_id', 'user', 'id')
            ->save();
    }

    public function down()
    {
        $table = $this->table('sftp');
        $table->dropForeignKey('user_id')
            ->removeColumn('user_id')
            ->save();
    }
}

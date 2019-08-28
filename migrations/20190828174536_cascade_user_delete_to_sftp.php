<?php


use Phinx\Migration\AbstractMigration;

class CascadeUserDeleteToSftp extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('sftp');
        $table->dropForeignKey('user_id')
            ->save();
        $table->addForeignKey('user_id', 'user', 'id', ['delete'=>'CASCADE'])
            ->save();
    }

    public function down()
    {
        $table = $this->table('sftp');
        $table->dropForeignKey('user_id')
            ->save();
        $table->addForeignKey('user_id', 'user', 'id')
            ->save();
    }
}

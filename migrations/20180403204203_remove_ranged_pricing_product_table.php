<?php


use Phinx\Migration\AbstractMigration;

class RemoveRangedPricingProductTable extends AbstractMigration
{
    public function up()
    {
        $this->table('rangedpricingproduct')
            ->drop();
    }

    public function down()
    {
        $this->table('rangedpricingproduct')
            ->create();
    }
}

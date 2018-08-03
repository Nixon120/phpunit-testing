<?php


use Phinx\Migration\AbstractMigration;

class AddCountryCodeInAddress extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('Address');
        $table->addColumn('country_code', 'string', ['limit' => 2, 'after' => 'country', 'null' => true])
            ->save();
    }
}

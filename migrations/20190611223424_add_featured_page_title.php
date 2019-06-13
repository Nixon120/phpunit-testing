<?php


use Phinx\Migration\AbstractMigration;

class AddFeaturedPageTitle extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('productcriteria');
        $table->addColumn('featured_page_title', 'string', ['null' => true])
              ->save();
    }
}

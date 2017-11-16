<?php
use Migrations\AbstractMigration;

class MyApp extends AbstractMigration
{
    public function up()
    {
        $this->table('images')
            ->addColumn('filename', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('width', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('height', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->create();
    }

    public function down()
    {
        $this->dropTable('images');
    }
}

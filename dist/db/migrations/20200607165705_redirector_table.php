<?php

use Phinx\Migration\AbstractMigration;

final class RedirectorTable extends AbstractMigration
{
    /**
     * Creates redirector table (use only if you use redirector feature)
     *
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     *
     * @return void
     */
    public function change()
    {
        $redirector = $this->table('redirector');
        $redirector
            ->addColumn('old_url', 'string', ['limit' => 500, 'comment' => 'URL to be redirected', 'null' => false])
            ->addIndex('old_url')
            ->addColumn('new_url', 'string', ['limit' => 500, 'comment' => 'Target URL', 'null' => false])
            // The DEFAULT CURRENT_TIMESTAMP support for a DATETIME (datatype) was added in MySQL 5.6.
            // In 5.5 and earlier versions, this applied only to TIMESTAMP (datatype) columns.
            ->addColumn('added', 'timestamp', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->create();
    }
}

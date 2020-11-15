<?php

use Phinx\Migration\AbstractMigration;

class RedirectorTable extends AbstractMigration
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
     */
    public function change()
    {
        $redirector = $this->table('redirector');
        $redirector
            ->addColumn('old_url', 'string', ['limit' => 500, 'comment' => 'URL to be redirected', 'null' => false])
            ->addIndex('old_url')
            ->addColumn('new_url', 'string', ['limit' => 500, 'comment' => 'Target URL', 'null' => false])
            ->addColumn('added', 'datetime', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->create();
    }
}

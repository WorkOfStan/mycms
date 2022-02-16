<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SubscriberTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     *
     * @return void
     */
    public function change() // : void
    {
        $subscriber = $this->table('subscriber');
        $subscriber
            ->addColumn('email', 'string', ['limit' => 255, 'comment' => 'Email entry from web', 'null' => false])
            ->addIndex(['email'], ['unique' => true])
            ->addColumn('info', 'string', ['limit' => 1024, 'comment' => 'IP address or other info', 'null' => false])
            // The DEFAULT CURRENT_TIMESTAMP support for a DATETIME (datatype) was added in MySQL 5.6.
            // In 5.5 and earlier versions, this applied only to TIMESTAMP (datatype) columns.
            ->addColumn('added', 'timestamp', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->create();
    }
}

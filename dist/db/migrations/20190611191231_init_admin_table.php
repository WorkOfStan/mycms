<?php

use Phinx\Migration\AbstractMigration;

class InitAdminTable extends AbstractMigration
{

    /**
     * Creates MyCMS administrators table with one default admin
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
        $admin = $this->table('admin');
        $admin
            ->addColumn('admin', 'string', ['limit' => 50, 'comment' => 'Admin username', 'null' => false])
            ->addIndex(['admin'], ['unique' => true])
            ->addColumn('salt', 'biginteger', ['signed' => false, 'comment' => 'Security salt', 'null' => false])
            ->addColumn('password_hashed', 'string', ['limit' => 40, 'comment' => 'Hashed password', 'null' => false])
            ->addColumn('rights', 'integer', ['comment' => 'Permission group', 'null' => false])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'null' => false, 'default' => 1])
            ->create();

        $singleRow = [
            'id' => 1,
            'admin' => 'john',
            'salt' => 141327478,
            'password_hashed' => '0a9a3657709db688184b9eae1b86f3466775357a',
            'rights' => 2,
            'active' => '1',
        ];

        $admin->insert($singleRow);
        $admin->saveData();
    }
}

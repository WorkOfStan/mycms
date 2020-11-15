<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ContentTable extends AbstractMigration
{

    /**
     * Creates table with content.
     * You may want to adapt language rows according to your needs.
     * And add special columns like cover_image, perex and so on according to your needs.
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
        require __DIR__ . '/../../conf/config.php';
        $languages = array_keys($myCmsConf['TRANSLATIONS']);
        sort($languages);

        $content = $this->table('content');
        $content
            ->addColumn('type', 'string', ['comment' => '{"display":"option", "display-own":1}', 'limit' => 100, 'null' => false,])
            ->addIndex(['type'])
            ->addColumn('code', 'string', ['comment' => 'page shortcut', 'default' => '', 'limit' => 100, 'null' => false,])
            ->create();

        foreach ($languages as $language) {
            $content
                ->addColumn('name_' . $language, 'string', ['comment' => 'Item name (required for listing)', 'default' => '', 'limit' => 200, 'null' => false,])
                ->addColumn('content_' . $language, 'text', ['comment' => '{"display":"html", "comment": "HTML content of the element"}', 'limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true,])
                ->addColumn('url_' . $language, 'string', ['comment' => 'FriendlyURL', 'limit' => 200, 'null' => true,])
                ->addIndex('url_' . $language)
                ->update();
        }

        $content
            ->addColumn('added', 'datetime', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('context', 'string', ['comment' => '{"edit":"json"}', 'limit' => 4096, 'null' => false,])
            ->addColumn('sort', 'integer', ['comment' => 'sort order', 'default' => 0, 'limit' => MysqlAdapter::INT_SMALL, 'null' => false,])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->addIndex(['active'])
            ->update();

        $category = $this->table('category');
        $category->create();

        // so that products can be placed into a default category
        $defaultCategory = [
            'id' => 1,
        ];

        foreach ($languages as $language) {
            $category
                ->addColumn('name_' . $language, 'string', ['comment' => 'Item name (required for listing)', 'default' => '', 'limit' => 200, 'null' => false,])
                ->addColumn('content_' . $language, 'text', ['comment' => '{"display":"html", "comment": "HTML content of the element"}', 'limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true,])
                ->addColumn('url_' . $language, 'string', ['comment' => 'FriendlyURL', 'limit' => 200, 'null' => true,])
                ->addIndex('url_' . $language)
                ->update();
            $defaultCategory['name_' . $language] = "Default {$language}";
            $defaultCategory['url_' . $language] = "default-category-{$language}";
        }

        $category
            ->addColumn('added', 'datetime', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('context', 'string', ['comment' => '{"edit":"json"}', 'default' => '{}', 'limit' => 4096, 'null' => false,])
            ->addColumn('sort', 'integer', ['comment' => 'sort order', 'default' => 0, 'limit' => MysqlAdapter::INT_SMALL, 'null' => false,])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->addIndex(['active'])
            ->insert($defaultCategory)
            ->update();

        $product = $this->table('product');

        $product
            ->addColumn('category_id', 'integer', [
                'comment' => '{"foreign-table":"category","foreign-column":"name_' . DEFAULT_LANGUAGE . '"}',
                'default' => 1
            ]) //category.id is expected
            ->addForeignKey('category_id', 'category', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addIndex('category_id')
            ->create();

        foreach ($languages as $language) {
            $product
                ->addColumn('name_' . $language, 'string', ['comment' => 'Item name (required for listing)', 'default' => '', 'limit' => 200, 'null' => false,])
                ->addColumn('content_' . $language, 'text', ['comment' => '{"display":"html", "comment": "HTML content of the element"}', 'limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true,])
                ->addColumn('url_' . $language, 'string', ['comment' => 'FriendlyURL', 'limit' => 200, 'null' => true,])
                ->addIndex('url_' . $language)
                ->update();
        }

        $product
            ->addColumn('added', 'datetime', ['comment' => 'Creation timestamp', 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('context', 'string', ['comment' => '{"edit":"json"}', 'limit' => 4096, 'null' => false,])
            ->addColumn('sort', 'integer', ['comment' => 'sort order', 'default' => 0, 'limit' => MysqlAdapter::INT_SMALL, 'null' => false,])
            ->addColumn('active', 'boolean', ['comment' => '0=inactive, 1=active', 'default' => 1])
            ->addIndex(['active'])
            ->update();
    }
}

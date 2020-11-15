<?php

//declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ContentExample extends AbstractMigration
{

    /**
     * Contains initial template for content of the web.
     * Adapt it here
     * or delete this migration before running it
     * or adapt the content in the database after this migration was used
     * - it is really up to you.
     *
     * Change Method.
     *
     */
    public function change() //: void
    {
        require __DIR__ . '/../../conf/config.php';
        $languages = array_keys($myCmsConf['TRANSLATIONS']);
        sort($languages);

        $category = $this->table('category');

        // so that products can be placed into a default category
        $anotherCategory = [
            'id' => 2,
            'context' => '[]',
        ];

        foreach ($languages as $language) {
            $anotherCategory['name_' . $language] = "2nd {$language}";
        }
        $category
            ->insert($anotherCategory)
            ->update();

        $product = $this->table('product');

        $productSample = [
            [
                'id' => 1, // TODO ověřit, zda lze vynutit id
                'category_id' => 1,
                'name_cs' => 'Produkt 1',
                'content_cs' => null,
                'url_cs' => 'alfa',
                'name_de' => 'Produkte 1',
                'content_de' => null,
                'url_de' => 'alfa',
                'name_en' => 'Product 1',
                'content_en' => null,
                'url_en' => 'alfa',
                'name_fr' => 'Produis 1',
                'content_fr' => null,
                'url_fr' => 'alfa',
                'context' => '[]',
            ],
            [
                'id' => 2, // TODO ověřit, zda lze vynutit id
                'category_id' => 1,
                'name_cs' => 'Produkt 2',
                'content_cs' => null,
                'url_cs' => 'beta',
//                'name_de' => 'Produkte 2',
//                'content_de' => null,
//                'url_de' => 'beta',
                'name_en' => 'Product 2',
                'content_en' => null,
                'url_en' => 'beta',
//                'name_fr' => 'Produis 2',
//                'content_fr' => null,
//                'url_fr' => 'beta',
                'context' => '[]',
            ],
            [
                'id' => 3, // TODO ověřit, zda lze vynutit id
                'category_id' => 1,
//                'name_cs' => 'Produkt 3',
//                'content_cs' => null,
//                'url_cs' => 'alfa',
                'name_de' => 'Produkte 3',
                'content_de' => null,
//                'url_de' => 'alfa',
                'name_en' => 'Product 3',
                'content_en' => null,
//                'url_en' => 'alfa',
//                'name_fr' => 'Produis 3',
//                'content_fr' => null,
//                'url_fr' => 'alfa',
                'context' => '[]',
            ],
            [
                'id' => 4, // TODO ověřit, zda lze vynutit id
                'category_id' => 1,
//                'name_cs' => 'Produkt 4',
//                'content_cs' => null,
//                'url_cs' => 'alfa',
//                'name_de' => 'Produkte 4',
//                'content_de' => null,
//                'url_de' => 'alfa',
                'name_en' => 'Product 4',
                'content_en' => null,
//                'url_en' => 'alfa',
                'name_fr' => 'Produis 4',
                'content_fr' => null,
//                'url_fr' => 'alfa',
                'context' => '[]',
            ],
            [
                'id' => 5, // TODO ověřit, zda lze vynutit id
                'category_id' => 1,
                'name_cs' => 'Produkt 5',
                'content_cs' => null,
//                'url_cs' => 'alfa',
//                'name_de' => 'Produkte 5',
//                'content_de' => null,
//                'url_de' => 'alfa',
//                'name_en' => 'Product 5',
//                'content_en' => null,
//                'url_en' => 'alfa',
//                'name_fr' => 'Produis 5',
//                'content_fr' => null,
//                'url_fr' => 'alfa',
                'context' => '[]',
            ],
        ];

        $product
            ->insert($productSample)
            ->update();

        $content = $this->table('content');

        $contentSample = [
            'id' => '1',
            'type' => 'article',
            'code' => 'contacts',
            'name_cs' => 'Kontakty',
            'content_cs' => '<p>Adresa:</p><p>Telefon:</p><p>E-mail:</p>',
            'url_cs' => 'kontakty',
            'name_de' => 'Kontakte',
            'content_de' => '<p>Adresse:</p><p>Telefon:</p><p>Email:</p>',
            'url_de' => 'kontakte',
            'name_en' => 'Contacts',
            'content_en' => '<p>Address:</p><p>Phone:</p><p>Email:</p>',
            'url_en' => 'contacts',
            'name_fr' => 'Contacts',
            'content_fr' => '<p>Adresse:</p><p>Téléphone:</p><p>Email:</p>',
            'url_fr' => 'contacts',
            'context' => '[]',
        ];

        $content
            ->insert($contentSample)
            ->update();

        $redirector = $this->table('redirector');

        $redirectorSample = [
            [
                'old_url' => '/adresa',
                'new_url' => '/kontakty',
            ],
            [
                'old_url' => '/adresa/do/nasi/kancelare',
                'new_url' => '/kontakty',
            ],
            [
                'old_url' => '/firma/adresa',
                'new_url' => '/kontakty',
            ],
        ];

        $redirector
            ->insert($redirectorSample)
            ->update();
    }
}

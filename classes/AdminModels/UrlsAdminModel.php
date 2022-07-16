<?php

namespace WorkOfStan\MyCMS\AdminModels;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Friendly URL management
 * Used by Admin::controller()
 *
 * TODO
 * - configure access to various places, where Friendly URL can be set (e.g. Products)
 *
 * @author rejthar@stanislavrejthar.com
 */
class UrlsAdminModel
{
    use \Nette\SmartObject;

    /** @var LogMysqli */
    protected $dbms = null;
    /** @var MyTableAdmin */
    protected $tableAdmin;

    /**
     * Constructor, expects a Database connection
     * @param LogMysqli $dbms The Database object
     * @param MyTableAdmin $tableAdmin for the translate method and TRANSLATIONS property
     */
    public function __construct(LogMysqli $dbms, MyTableAdmin $tableAdmin)
    {
        $this->dbms = $dbms;
        $this->tableAdmin = $tableAdmin;
    }

    /**
     * Friendly URL: one place to set them all, identify duplicities
     *
     * @return string
     */
    public function htmlOutput()
    {
        // One place to set Friendly URL for all pages
        // originally code F (delete this line later)
        $output = '<h1><i class="fa fa-link"></i> ' . $this->tableAdmin->translate('Friendly URL')
            . '</h1><div id="agenda-urls">'
            . '<form action="" method="post" class="friendly-urls" onsubmit="return confirm(\''
            . $this->tableAdmin->translate('Are you sure?') . '\')">'
            . Tools::htmlInput('urls', '', 1, ['type' => 'hidden'])
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden');
        $urls = []; // all URLs, all language versions with a link to what it links to (product, page, … id, etc.)
        $langs = array_keys($this->tableAdmin->TRANSLATIONS);
        // Todo queryStrictArray
        $query = $this->dbms->queryStrictNonEmptyArray(
            'SELECT id,"content" AS _table,type,' . Tools::arrayListed($langs, 0, ',', 'url_') . ','
            . Tools::arrayListed($langs, 0, ',', 'name_') . ' FROM `' . TAB_PREFIX . 'content` WHERE type IN ('
            . '"article", "page", "news"' // list of types to be listed for Friendly URL settings
            . ') ORDER BY type'
        );
        foreach ($query as $row) {
            $urls [] = $row;
        }
        // Friendly URL folders for types to be listed for Friendly URL settings
        $TYPE2PATH = [
            'content-article' => '',
            'content-page' => '',
            'content-news' => 'news'
        ];
        $lastType = false;
        foreach ($urls as $value) {
            Assert::isArray($value);
            if ($lastType != $value['_table'] . '-' . $value['type']) {
                $output .= '<h3 class="lead">' . Tools::h($lastType = $value['_table'] . '-' . $value['type'])
                    . '</h3>' . PHP_EOL;
            }
            $output .= '<div class="mb-3"><div><a href="?table=' . urlencode(TAB_PREFIX . $value['_table'])
                . '&where[id]=' . (int) $value['id'] . '" target="_blank">'
                . '<i class="fa fa-external-link"></i></a> ' .
                (Tools::h($value['name_' . DEFAULT_LANGUAGE]) ?: '<i>N/A</i>') . '</div>';
            foreach ($langs as $key => $lang) {
                // TODO should trailing slash be present?
                $value['fill'] = rtrim('/' . Tools::wrap($TYPE2PATH[$lastType], '', '/') .
                    /* $value['id'] . '-' . */ Tools::webalize($value["name_$lang"]), '-');
                $output .= '<div class="input-group input-group-sm">'
                    . '<div class="input-group-prepend"><tt class="input-group-text btn" title="'
                    . $this->tableAdmin->translate('Fill up') . '">' . $lang . '</tt></div>'
                    . Tools::htmlInput(
                        'url-' . urlencode($value['_table']) . '-' . $value['id'] . '-' . $lang,
                        '',
                        $value["url_$lang"],
                        array('class' => 'form-control monospace', 'data-fill' => $value['fill'])
                    )
                    . '</div>' . PHP_EOL;
            }
            $output .= '</div>';
        }
        $output .= '<p><button class="btn btn-primary mr-1" type="submit" name="urls-save"><i class="fa fa-save"></i> '
            . $this->tableAdmin->translate('Save') . '</button>
            <button class="btn btn-secondary btn-fill" type="button"><i class="fa fa-edit"></i> '
            . $this->tableAdmin->translate('Fill up') . '</button>
            ' . Tools::htmlInput(
                '',
                $this->tableAdmin->translate('only empty'),
                '',
                array('type' => 'checkbox', 'id' => 'only-empty', 'label-after' => true, 'label-class' => 'mx-1')
            ) . '
            <button class="btn btn-secondary btn-check-up" type="button"><i class="fa fa-eye"></i> '
            . $this->tableAdmin->translate('Check up') . '</button>
            </p></form>';

        // Identify duplicit URLs
        // originally code A (delete this line later)
        $output .= '<hr><h1><i class="fa fa-unlink"></i> ' . $this->tableAdmin->translate('Duplicit URL') . '</h1>'
            . '<p>' . $this->tableAdmin->translate('Duplicities may appear across languages.') . '</p>'
            . '<div id="agenda-urls">';
        $urls = [];
        foreach (
            [
            // Note: not all apps have all those tables
            'category',
            'content',
            'product'
            ] as $table
        ) {
            foreach (array_keys($this->tableAdmin->TRANSLATIONS) as $i) {
                foreach (
                    $this->dbms->fetchAll("SELECT COUNT(url_$i) AS _count, url_$i AS url"
                    . ' FROM `' . TAB_PREFIX . "{$table}` GROUP BY url ORDER BY _count DESC") as $row
                ) {
                    // Tools::add($urls[$row['url']], $row['_count']); // next line is more static analysis friendly:
                    $urls[$row['url']] = (isset($urls[$row['url']]) ? $urls[$row['url']] : 0) + $row['_count'];
                }
            }
        }
        foreach ($urls as $key => $value) {
            if ($value <= 1) {
                unset($urls[$key]);
            }
        }
        foreach (array_keys($urls) as $url) {
            $sql = [];
            foreach (['category', 'content', 'product'] as $table) {
                $sql [] = "SELECT '$table' AS type,id,name" . '_' . $_SESSION['language']
                    . ' AS name FROM `' . TAB_PREFIX . "{$table}` WHERE " .
                    Tools::arrayListed(
                        array_keys($this->tableAdmin->TRANSLATIONS),
                        0,
                        ' OR ',
                        'url_',
                        '="' . $this->dbms->escapeSQL((string) $url) . '"'
                    );
            }
            $query = $this->dbms->fetchAll(implode(" UNION\n", $sql));
            $output .= '<details><summary>' . Tools::h((string) $url) . ' <sup class="badge badge-secondary">'
                . count($query) . '</sup></summary>';
            foreach ($query as $row) {
                $output .= '<div class="ml-2"><a href="?table=' . TAB_PREFIX . $row['type'] . '&amp;where[id]='
                    . $row['id'] . '"><i class="fa fa-table"></i> ' . Tools::h((string) $row['name'])
                    . ' (' .
//                    $this->tableAdmin->translate(
                    $row['type']
//                        )
                    .
                    ')</a></div>' . PHP_EOL;
            }
            $output .= '</details>' . PHP_EOL;
        }
        $output .= (count($urls) ? '' : '<i>' . $this->tableAdmin->translate('None') . '</i>')
            . '</div><footer class="mt-2">'
            . (count($urls) ? '<button type="button" class="btn btn-sm btn-secondary mr-2" id="urls-toggle" title="'
            . $this->tableAdmin->translate('Open/close')
            . '" data-open="1"><i class="fas fa-caret-right"></i> <i class="fas fa-caret-down"></i></button>' : '')
            . '</footer>';
        return $output;
    }
}

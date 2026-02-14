<?php

namespace WorkOfStan\mycmsprojectnamespace\AdminModels;

use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Pages management
 * Used by Admin::controller()
 *
 * @author rejthar@stanislavrejthar.com
 */
class PagesAdminModel
{
    use \Nette\SmartObject;

    /** @var LogMysqli */
    protected $dbms = null;
    /** @var MyTableAdmin */
    protected $tableAdmin;

    /**
     * Constructor, expects a Database connection
     * @param LogMysqli $dbms The Database object
     * @param MyTableAdmin $tableAdmin for the translate method
     */
    public function __construct(LogMysqli $dbms, MyTableAdmin $tableAdmin)
    {
        $this->dbms = $dbms;
        $this->tableAdmin = $tableAdmin;
    }

    /**
     *
     * @return string
     */
    public function htmlOutput(): string
    {
        $output = '';
        // pages // TODO make it do something useful in Dist
        $output .= '<h1>' . $this->tableAdmin->translate('Pages') . '</h1><div id="agenda-pages">';
        $categories = $this->dbms->fetchAndReindexStrictArray('SELECT id,'
            //. 'path,' // TODO path was used in A project. Reconsider here.
            . 'active,name_'
            . $_SESSION['language'] . ' AS category FROM `' . TAB_PREFIX . 'category`');
        // TODO path was used in A project. Reconsider here.
        // . ' WHERE LEFT(path, ' . PATH_MODULE . ')="'
        // . $this->MyCMS->escapeSQL($this->MyCMS->SETTINGS['PATH_HOME']) . '" ORDER BY path');
        //\Tracy\Debugger::barDump($categories, 'CATEGORIES'); // temp
        $articles = $this->dbms->fetchAndReindexStrictArray('SELECT '
            //. 'category_id,' // TODO category_id was used in A project to link content rows. Reconsider here.
            . 'id,active,IF(content_'
            . $_SESSION['language'] . ' = "", LEFT(CONCAT(code, " ", content_'
            . $_SESSION['language'] . '), 100),content_' . $_SESSION['language'] . ') AS content
                FROM `' . TAB_PREFIX . 'content`');
        //. ' WHERE category_id > 0'); // TODO category_id was used in A project to link content rows.
        //Reconsider here.
        //\Tracy\Debugger::barDump($categories, 'CATEGORIES'); // temp
        foreach ($categories as $key => $category) {
            Assert::isArray($category);
            /* TODO this code probably should display articles related to categories, Reconsider - Implement?
              Assert::isCountable($articles[$key]);
              $tmp = isset($articles[$key][0]) ? count($articles[$key]) : (isset($articles[$key]) ? 1 : 0);
              $output .= '<details '
              // TODO consider PATH from A project to provide tree like display
              //. 'style="margin-left:' . (strlen((string) $category['path']) / PATH_MODULE - 1) . 'em"'
              . ($category['active'] == 1 ? '' : ' class="inactive-item"') . '>
              <summary class="d-inline-block">'
              . '<a href="?table=' . TAB_PREFIX . 'category&amp;where[id]=' . $key .
              '"><i class="fas fa-edit"></i></a> '
              . '<a href="index.php?category&id=' . $key .
              '" target="_blank"><i class="fas fa-external-link-alt"></i></a> '
              . '<button class="category-switch btn btn-xs" value="-1" data-id="' . $key . '" title="' .
              $this->tableAdmin->translate('Move up') . '"><i class="fa fa-arrow-up"></i></button> '
              . '<button class="category-switch btn btn-xs" value="1" data-id="' . $key . '" title="' .
              $this->tableAdmin->translate('Move down') . '"><i class="fa fa-arrow-down"></i></button> '
              . '<span' . ($category['active'] == 1 ? '' : ' class="inactive"') . '>' .
              Tools::h((string) $category['category']) . '</span>'
              . ' <sup class="badge badge-' . ($tmp ? 'info' : 'warning') . '"><small>' . $tmp .
              '</small></sup></summary>'
              . '<div class="ml-3">';
              if (isset($articles[$key])) {
              $tmp = isset($articles[$key][0]) ? $articles[$key] : [$articles[$key]];
              Assert::isArray($tmp);
              foreach ($tmp as $id => $article) { // $id is article id
              Assert::isArray($article);
              \Tracy\Debugger::barDump($article, 'ARTICLE'); // temp
              $output .= '<div' . ($article['active'] == 1 ? '' : ' class="inactive-item"') . '>'
              . '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $id .
              '"><small class="far fa-edit"></small></a> '
              . '<a href="index.php?article&amp;id=' . $id .
              '"><small class="fas fa-external-link-alt"></small></a> '
              . '<span' . ($article['active'] == 1 ? '' : ' class="inactive"') . '>' .
              strip_tags((string) $article['content']) . '</span></div>' . PHP_EOL;
              }
              }
              $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[]=&amp;prefill[category_id]=' . $key .
              '&amp;prefill[type]=page">'
              . '<i class="far fa-plus-square"></i></a> ' . $this->tableAdmin->translate('New record') . '</div>'
              . '</details>' . PHP_EOL;
             */
        }
        $articles = $this->dbms->fetchAndReindex(
            'SELECT 0, id, IF(content_' . $_SESSION['language']
            . ' = "", LEFT(CONCAT(code, " ", content_' . $_SESSION['language'] . '), 100),'
            . ' content_' . $_SESSION['language'] . ') AS content FROM `' . TAB_PREFIX . 'content`'
        );
        //. ' WHERE category_id IS NULL AND product_id IS NULL'); // used in project A - TODO reconsider
        if ($articles) {
            $output .= '<details><summary><tt>NULL</tt></summary>';
            if (
                $tmp = $this->dbms->fetchAndReindex(
                    'SELECT id,name_' . $_SESSION['language'] . ' AS name FROM `' . TAB_PREFIX . 'category`'
                )
                //. ' WHERE path IS NULL') // TODO reconsider this from project A
            ) {
                foreach ($tmp as $key => $category) {
                    Assert::string($category);
                    $output .= '<a href="?table=' . TAB_PREFIX . 'category&amp;where[id]=' . $key
                        . '" class="ml-3"><i class="fa fa-edit"></i></a> ' . strip_tags($category)
                        . '<br />' . PHP_EOL;
                }
            }
            Assert::isIterable($articles[0]);
            foreach ($articles[0] as $article) {
                Assert::isArray($article);
                Assert::string($article['id']);
                $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $article['id']
                    . '" class="ml-3"><i class="far fa-file"></i></a> ' . strip_tags((string) $article['content'])
                    . '<br />' . PHP_EOL;
            }
            $output .= '</details>';
        }
        $output .= '<footer>
                    <button type="button" class="btn btn-sm btn-secondary mr-2" id="pages-actives" title="'
            . $this->tableAdmin->translate('Toggle inactive') . '"><i class="far fa-eye-slash"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary mr-2" id="pages-toggle" title="'
            . $this->tableAdmin->translate('Open/close') . '" data-open="1"><i class="fas fa-caret-right"></i>'
            . ' <i class="fas fa-caret-down"></i></button></footer></div>';

        //Assert::string($output);
        return $output;
    }
}

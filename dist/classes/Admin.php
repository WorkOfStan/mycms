<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyAdmin;
use GodsDev\MyCMS\MyCMS;
use GodsDev\Tools\Tools;

class Admin extends MyAdmin
{
    use \Nette\SmartObject;

    /** @var array<array> tables and columns to search in admin */
    protected $searchColumns = [
        'category' => ['id', 'category_#', 'description_#'], // "#" will be replaced by current language
        'content' => ['id', 'content_#', 'description_#'], // "#" will be replaced by current language
        'product' => ['id', 'product_#', 'description_#'], // "#" will be replaced by current language
    ];

    /**
     *
     * @param MyCMS $MyCMS
     * @param array<mixed> $options overrides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        $this->clientSideResources['js'][] = 'scripts/Cookies.js';
        parent::__construct($MyCMS, $options);
    }

    /**
     * Output (in HTML) the project-specific links in the navigation section of admin
     * TODO: navázat na další features
     *
     * @return string
     */
    protected function outputSpecialMenuLinks()
    {
        return
            // A Produkty k řazení
            '<li class="nav-item' . (isset($_GET['products']) ? ' active' : '') . '"><a href="?products" class="nav-link"><i class="fas fa-gift"></i> ' . $this->tableAdmin->translate('Products') . '</a></li>'
            // A Stránky k řazení
            . '<li class="nav-item' . (isset($_GET['pages']) ? ' active' : '') . '"><a href="?pages" class="nav-link"><i class="far fa-file-alt"></i> ' . $this->tableAdmin->translate('Pages') . '</a></li>'
            // A URL - - kontrola duplicit a Obrázky a Odkazy
            . '<li class="nav-item' . (isset($_GET['urls']) ? ' active' : '') . '"><a href="?urls" class="nav-link"><i class="fas fa-unlink"></i> URL</a></li>'
            // F Divize a produkty k řazení (jako A Produkty k řazení)
            . '<li class="nav-item"><a href="?divisions-products" class="nav-link' . (isset($_GET['divisions-products']) ? ' active' : '') . '"><i class="fa fa-gift mr-1" aria-hidden="true"></i> ' . $this->tableAdmin->translate('Divisions and products') . '</a></li>'
            // F drop-down menu
            . '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle' . (isset($_GET['urls']) || isset($_GET['translations']) ? ' active' : '') . '" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><i class="fas fa-lightbulb"></i></a>'
            . '<div class="dropdown-menu" aria-labelledby="navbarDropdown">'
            // F přátelské URL
            . '<a href="?urls" class="dropdown-item' . (isset($_GET['urls']) ? ' active' : '') . '"><i class="fa fa-link mr-1" aria-hidden="true"></i> ' . $this->tableAdmin->translate('Friendly URL') . '</a>'
            // F Překlady
            . '<a href="?translations" class="dropdown-item' . (isset($_GET['translations']) ? ' active' : '') . '"><i class="fa fa-globe mr-1" aria-hidden="true"></i> ' . $this->tableAdmin->translate('Translations') . '</a>
            </div></li>';
    }

    /**
     * Output (in HTML) project-specific code before listing of a table
     * Selected tables are filtered by type field, i.e. type=SELECTION filter is pre-filled
     * TODO: consider moving this code (with parametric table name) to MyAdmin
     *
     * @return string
     */
    protected function outputTableBeforeListing()
    {
        return (in_array(mb_substr($_GET['table'], mb_strlen(TAB_PREFIX)), ['content'])) ?
            $this->tableAdmin->contentByType() : '';
    }

    /**
     * Output (in HTML) project-specific code after editing a record from selected table
     *
     * @return string
     */
    protected function outputTableAfterEdit()
    {
        $output = '';
        if (isset($_GET['where']['id']) && $_GET['where']['id']) { //existing record
            switch ($_GET['table']) {
                case TAB_PREFIX . 'category':
                    // Display related products and content elements labeled by either name or content fragmet (up to 100 characters)
                    // TODO link content elements to category
                    foreach (['content', 'product'] as $i) {
                        if ($tmp = $this->MyCMS->fetchAndReindex(
                            'SELECT id,IF(name_'. $_SESSION['language'] . ' NOT LIKE "",name_'.$_SESSION['language'].', content_' . $_SESSION['language'] . ') FROM ' . TAB_PREFIX . $i . ' WHERE category_id=' . (int)$_GET['where']['id']
                        )) {
                            $output .= '<hr /><details><summary>' . $this->tableAdmin->translate($i == 'content' ? 'Content linked to this category' : 'Products linked to this category') . ' <span class="badge badge-secondary">' . count($tmp) . '</span></summary>';
                            foreach ($tmp as $key => $value) {
                                $output .= '<a href="?table=' . TAB_PREFIX . $i . '&amp;where[id]=' . $key . '" target="_blank" title="' . $this->tableAdmin->translate('Link will open in a new window'). '">'
                                    . '<i class="fas fa-external-link-alt"></i></a> ' . substr(Tools::h($value), 0, 100) . '<br />' . PHP_EOL;
                            }
                            $output .='</details>';
                        }
                    }
                    break;
                case TAB_PREFIX . 'product':
                    // Display related content elements labeled by either name or content fragmet (up to 100 characters)
                    // TODO link content elements to products
                    $output .='<hr /><details class="product-linked-content"><summary>' . $this->tableAdmin->translate('Content linked to this product') . ' <span class="badge badge-secondary">';
                    if ($tmp = $this->MyCMS->fetchAndReindex('SELECT id,content_' . $_SESSION['language'] . ' AS content,description_' . $_SESSION['language'] . ' AS description FROM ' . TAB_PREFIX . 'content WHERE product_id=' . (int)$_GET['where']['id'])) {
                        $output .= count($tmp) . '</span></summary>';
                        foreach ($tmp as $key => $row) {
                            $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $key . '" target="_blank" title="' . $this->tableAdmin->translate('Link will open in a new window'). '">'
                                . '<i class="fas fa-external-link-alt"></i> ' . Tools::h(mb_substr(strip_tags($row['content']), 0, 100)) . ' ' . Tools::h(mb_substr(strip_tags($row['description']), 0, 100)) . 'â€¦</a><br />' . PHP_EOL;
                        }
                    } else {
                        $output .= '0</span></summary>';
                    }
                    $output .= '<footer>';
                    foreach (['testimonial', 'claim', 'perex'] as $i) {
                        $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[]=&amp;prefill[type]=' . $i . '&amp;prefill[product_id]=' . Tools::ifnull($_GET['where']['id'], '') . '" '
                            . 'title="' . $this->tableAdmin->translate('New row') . ' (' . $this->tableAdmin->translate('Link will open in a new window') . ')" '
                            . 'target="_blank"><i class="far fa-plus-square"></i> <i class="fas fa-external-link-alt"></i> ' . $i . '</a>';
                    }
                    $output .= '</footer></details>';
                    break;
            }
        }
        return $output;
    }

    /**
     * @return bool
     */
    protected function projectSpecificSectionsCondition()
    {
        // TO BE EXPLORED
        return parent::projectSpecificSectionsCondition();
    }

    /**
     * Process and return HTML of the project-specific admin sections.
     *
     * @return string
     */
    protected function projectSpecificSections()
    {
        // to be explored
        return parent::projectSpecificSections();
    }

    /**
     * Called from projectSpecificSections
     *
     * @return string
     */
    protected function sectionDivisionsProducts()
    {
        // to be explored
        return '';
    }

    /**
     * Called from projectSpecificSections
     *
     * @return string
     */
    protected function sectionTranslations()
    {
        // to be explored
        return '';
    }

    /**
     * As vendor folder has usually denied access from browser,
     * the content of the standard admin.css MUST be available through this method
     *
     * @return string
     */
    public function getAdminCss()
    {
        return parent::getAdminCss() . PHP_EOL . file_get_contents(__DIR__ . '/../styles/admin.css') . PHP_EOL;
    }

    /**
     * Add project specific titles
     * TODO: test the A inspiration
     *
     * @return string
     */
    public function getPageTitle()
    {
        return parent::getPageTitle() ?:
            (
                isset($_GET['pages']) ? $this->TableAdmin->translate('Pages') :
            (
                isset($_GET['products']) ? $this->TableAdmin->translate('Products') :
            (
                isset($_GET['urls']) ? $this->TableAdmin->translate('URL') :
            ''
            )
            )
            );
    }
}

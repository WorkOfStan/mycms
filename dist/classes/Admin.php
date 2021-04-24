<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyAdmin;
use GodsDev\MyCMS\MyCMS;

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
     * TODO: consider moving this code abstracted to MyAdmin
     *
     * @return string
     */
    protected function outputTableBeforeListing()
    {
        return (in_array(mb_substr($_GET['table'], mb_strlen(TAB_PREFIX)), ['content'])) ?
            $this->tableAdmin->contentByType() : '';
    }

    /**
     * Output (in HTML) project-specific code after listing of a table
     *
     * @return string
     */
    protected function outputTableAfterEdit()
    {
        // TO BE EXPLORED
        return parent::outputTableAfterEdit();
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

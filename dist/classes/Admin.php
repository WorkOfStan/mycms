<?php

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\AdminModels\TranslationsAdminModel;
use WorkOfStan\MyCMS\AdminModels\UrlsAdminModel;
use WorkOfStan\MyCMS\L10n;
use WorkOfStan\MyCMS\MyAdmin;
use WorkOfStan\mycmsprojectnamespace\MyCMSProject;

use function WorkOfStan\MyCMS\ThrowableFunctions\glob;
use function WorkOfStan\MyCMS\ThrowableFunctions\preg_match_all;

/**
 * Admin UI
 * (Last MyCMS/dist revision: 2023-03-09, v0.4.8)
 *
 * Feature flag 'legacy_admin_methods_instead_of_admin_models' => true can turn off the new AdminModels
 * in favour of the legacy spagetti code within this class
 */
class Admin extends MyAdmin
{
    use \Nette\SmartObject;

    /** @var string Folder and name prefix of localisation yml for the web UI (not admin UI) */
    protected $prefixUiL10n;

    /**
     *
     * @param MyCMSProject $MyCMS
     * @param array<mixed> $options overrides default values of properties
     */
    public function __construct(MyCMSProject $MyCMS, array $options = [])
    {
        $this->clientSideResources['js'][] = 'scripts/Cookies.js';
        parent::__construct($MyCMS, $options);
    }

    /**
     * Project-specific change of the default Admin UI Controller
     *
     * @return void
     */
    protected function controller()
    {
        // TODO refactor this into pure Latte
        if (
            array_key_exists('table', $this->get) && !empty($this->get['table']) && (bool) $this->tableAdmin->getTable()
        ) {
            if (!array_key_exists('table', $this->renderParams) || !is_array($this->renderParams['table'])) {
                $this->renderParams['table'] = [];
            }
            if (isset($this->get['where']) && is_array($this->get['where'])) {
                $this->renderParams['table']['outputTableAfterEdit'] = $this->outputTableAfterEdit();
            } elseif (!isset($_POST['edit-selected']) && !isset($_POST['clone-selected'])) {
                $this->renderParams['table']['outputTableBeforeListing'] = $this->outputTableBeforeListing();
            }
        }

        // changes of inherited Lattes MUST be done before invoking the parent::controller();
        parent::controller(); // selects $this->template according to $myCmsConfAdmin['get2template']
        if (!isset($_SESSION['user'])) { // todo explore if it is sufficient for auth - consider (bool) $this->authUser
            //$this->template = 'admin-login'; //ready
            //todo MOVE THIS to parent
//            unset($this->get['table'], $this->get['media'], $this->get['user']); // security by design
            $this->get[] = []; // security by design
            $this->renderParams['htmlOutput'] = $this->outputLogin();
            return; //harden auth security TODO explore security that no other conditions will be allowed if !user
        }

        // TODO check whether unavailable for anonymous users!
        switch ($this->template) {
            case 'Admin/divisions-products':
                $this->renderParams['pageTitle'] = $this->tableAdmin->translate('Divisions and products');
                if (
                    isset($this->featureFlags['legacy_admin_methods_instead_of_admin_models']) &&
                    $this->featureFlags['legacy_admin_methods_instead_of_admin_models']
                ) {
                    // legacy since 220620
                    $this->renderParams['htmlOutput'] = $this->projectSpecificSections(); // in the Admin
                } else {
                    $adminModel = new AdminModels\DivisionsProductsAdminModel(
                        $this->MyCMS->dbms,
                        $this->tableAdmin
                    );
                    $this->renderParams['htmlOutput'] = $adminModel->htmlOutput();
                }
                break;
            case 'Admin/pages':
                $this->renderParams['pageTitle'] = $this->tableAdmin->translate('Pages');
                if (
                    isset($this->featureFlags['legacy_admin_methods_instead_of_admin_models']) &&
                    $this->featureFlags['legacy_admin_methods_instead_of_admin_models']
                ) {
                    // legacy since 220620
                    $this->renderParams['htmlOutput'] = $this->projectSpecificSections(); // in the Admin
                } else {
                    $adminModel = new AdminModels\PagesAdminModel(
                        $this->MyCMS->dbms,
                        $this->tableAdmin
                    );
                    $this->renderParams['htmlOutput'] = $adminModel->htmlOutput();
                }
                break;
            case 'Admin/products':
                $this->renderParams['pageTitle'] = $this->tableAdmin->translate('Products');
                if (
                    isset($this->featureFlags['legacy_admin_methods_instead_of_admin_models']) &&
                    $this->featureFlags['legacy_admin_methods_instead_of_admin_models']
                ) {
                    // legacy since 220620
                    $this->renderParams['htmlOutput'] = $this->projectSpecificSections(); // in the Admin
                } else {
                    $adminModel = new AdminModels\ProductsAdminModel(
                        $this->MyCMS->dbms,
                        $this->tableAdmin,
                        $this->MyCMS->logger
                    );
                    $this->renderParams['htmlOutput'] = $adminModel->htmlOutput();
                }
                break;
            case 'Admin/translations':
                $this->renderParams['pageTitle'] = $this->tableAdmin->translate('Translations');
                if (
                    isset($this->featureFlags['legacy_admin_methods_instead_of_admin_models']) &&
                    $this->featureFlags['legacy_admin_methods_instead_of_admin_models']
                ) {
                    // legacy since 220620
                    $this->renderParams['htmlOutput'] = $this->projectSpecificSections(); // in the Admin
                } else {
                    $adminModel = new TranslationsAdminModel(
                        $this->MyCMS->dbms,
                        $this->tableAdmin,
                        $this->prefixUiL10n
                    );
                    $this->renderParams['htmlOutput'] = $adminModel->htmlOutput();
                }
                break;
            case 'Admin/urls':
                $this->renderParams['pageTitle'] = $this->tableAdmin->translate('URL');
                if (
                    isset($this->featureFlags['legacy_admin_methods_instead_of_admin_models']) &&
                    $this->featureFlags['legacy_admin_methods_instead_of_admin_models']
                ) {
                    // legacy since 220620
                    $this->renderParams['htmlOutput'] = $this->projectSpecificSections(); // in the Admin
                } else {
                    $adminModel = new UrlsAdminModel(
                        $this->MyCMS->dbms,
                        $this->tableAdmin
                    );
                    $this->renderParams['htmlOutput'] = $adminModel->htmlOutput();
                }
                break;
        }
    }

    /**
     * Output (in HTML) the project-specific links in the navigation section of admin
     * TODO: navázat na další features
     *
     * @deprecated 0.4.7 Set `$featureFlags['admin_latte_render'] = true;` instead.
     * @see template/Admin/inc-special-menu-links.latte
     * @return string
     */
    protected function outputSpecialMenuLinks()
    {
        return
            // A Produkty k řazení
            (Tools::nonzero($this->featureFlags['order_hierarchy']) ? (
                '<li class="nav-item' . (isset($this->get['products']) ? ' active' : '')
            . '"><a href="?products" class="nav-link"><i class="fas fa-gift"></i> '
            . $this->tableAdmin->translate('Products') . '</a></li>'
            ) : '')
            // A Stránky k řazení
            . (Tools::nonzero($this->featureFlags['order_hierarchy']) ? (
                '<li class="nav-item' . (isset($this->get['pages']) ? ' active' : '')
            . '"><a href="?pages" class="nav-link"><i class="far fa-file-alt"></i> '
            . $this->tableAdmin->translate('Pages') . '</a></li>'
            ) : '')
            // URLs - (Friendly URL set-up and) check duplicities
            . '<li class="nav-item' . (isset($this->get['urls']) ? ' active' : '')
            . '"><a href="?urls" class="nav-link"><i class="fas fa-unlink"></i> URL</a></li>'
            // F Divize a produkty k řazení (jako A Produkty k řazení)
            . (Tools::nonzero($this->featureFlags['order_hierarchy']) ? (
                '<li class="nav-item"><a href="?divisions-products" class="nav-link'
            . (isset($this->get['divisions-products']) ? ' active' : '') .
            '"><i class="fa fa-gift mr-1" aria-hidden="true"></i> ' .
            $this->tableAdmin->translate('Divisions and products') . '</a></li>'
            ) : '')
            // F drop-down menu
            . '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle'
            . (isset($this->get['urls']) || isset($this->get['translations']) ? ' active' : '')
            . '" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"'
            . ' aria-haspopup="true" aria-expanded="true"><i class="fas fa-lightbulb"></i></a>'
            . '<div class="dropdown-menu" aria-labelledby="navbarDropdown">'
            // URLs - Friendly URL set-up (and check duplicities)
            . '<a href="?urls" class="dropdown-item' . (isset($this->get['urls']) ? ' active' : '')
            . '"><i class="fa fa-link mr-1" aria-hidden="true"></i> '
            . $this->tableAdmin->translate('Friendly URL') . '</a>'
            // F Překlady
            . '<a href="?translations" class="dropdown-item' . (isset($this->get['translations']) ? ' active' : '')
            . '"><i class="fa fa-globe mr-1" aria-hidden="true"></i> '
            . $this->tableAdmin->translate('Translations') . '</a>
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
        return (in_array(mb_substr($this->tableAdmin->getTable(), mb_strlen(TAB_PREFIX)), ['content'])) ?
            $this->tableAdmin->contentByType(['table' => 'content', 'type' => 'type']) : '';
    }

    /**
     * Output (in HTML) project-specific code after editing a record from selected table
     *
     * @return string
     */
    protected function outputTableAfterEdit()
    {
        $output = '';
        if (is_array($this->get['where']) && isset($this->get['where']['id']) && $this->get['where']['id']) {
            //existing record
            switch ($this->get['table']) {
                case TAB_PREFIX . 'category':
                    // Display related products and content elements labeled by either name
                    // or content fragment (up to 100 characters)
                    // TODO link content elements to category
                    foreach (
                        [
                            // 'content', // uncomment if some content table rows would be linked to a category
                            'product'
                        ] as $i
                    ) {
                        Assert::scalar($this->get['where']['id']);
                        if (
                            $tmp = $this->MyCMS->fetchAndReindex(
                                'SELECT id,IF(name_' . $_SESSION['language'] . ' NOT LIKE "",name_'
                                . $_SESSION['language'] . ', content_' . $_SESSION['language'] . ')'
                                . ' FROM `' . TAB_PREFIX . $i . '` WHERE category_id=' . (int) $this->get['where']['id']
                            )
                        ) {
                            $output .= '<hr /><details><summary>' .
                                // uncomment 'content' cond. if some content table rows would be linked to a category
                                $this->tableAdmin->translate(
                                    //$i == 'content' ? 'Content linked to this category' :
                                    'Products linked to this category'
                                )
                                . ' <span class="badge badge-secondary">' . count($tmp) . '</span></summary>';
                            foreach ($tmp as $key => $value) {
                                Assert::nullOrString($value);
                                if (empty($value)) { // covers both null and ''
                                    // if the product or content piece doesn't have a label in admin language
                                    $value = $this->tableAdmin->translate('Name not set');
                                }
                                $output .= '<a href="?table=' . TAB_PREFIX . $i . '&amp;where[id]=' . $key
                                    . '" target="_blank" title="'
                                    . $this->tableAdmin->translate('Link will open in a new window') . '">'
                                    . '<i class="fas fa-external-link-alt"></i></a> '
                                    . substr(Tools::h(strip_tags($value)), 0, 100) . '<br />' . PHP_EOL;
                            }
                            $output .= '</details>';
                        }
                    }
                    break;
//                case TAB_PREFIX . 'product':
//                    // Display related content elements labeled by either name or content fragmet (up to 100 chars)
//                    // TODO link content elements to products - otherwise it fails because of `WHERE product_id=`
//                    $output .= '<hr /><details class="product-linked-content"><summary>' .
//                        $this->tableAdmin->translate('Content linked to this product') .
//                        ' <span class="badge badge-secondary">';
//                    if ($tmp = $this->MyCMS->fetchAndReindex('SELECT id,name_' . $_SESSION['language'] .
//                        ' AS name,content_' . $_SESSION['language'] . ' AS content'
//                        . ' FROM `' . TAB_PREFIX . 'content` WHERE product_id=' . (int) $this->get['where']['id'])) {
//                        $output .= count($tmp) . '</span></summary>';
//                        foreach ($tmp as $key => $row) {
//                            $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[id]=' . $key
//                                . '" target="_blank" title="'
//                                . $this->tableAdmin->translate('Link will open in a new window') . '">'
//                                . '<i class="fas fa-external-link-alt"></i> '
//                                . Tools::h(mb_substr(strip_tags($row['content']), 0, 100)) . ' '
//                                . Tools::h(mb_substr(strip_tags($row['description']), 0, 100))
//                                . '…</a><br />' . PHP_EOL;
//                        }
//                    } else {
//                        $output .= '0</span></summary>';
//                    }
//                    $output .= '<footer>';
//                    foreach (['testimonial', 'claim', 'perex'] as $i) {
//                        $output .= '<a href="?table=' . TAB_PREFIX . 'content&amp;where[]=&amp;prefill[type]=' . $i
//                            . '&amp;prefill[product_id]=' . Tools::ifnull($this->get['where']['id'], '') . '" '
//                            . 'title="' . $this->tableAdmin->translate('New row')
//                            . ' (' . $this->tableAdmin->translate('Link will open in a new window') . ')" '
//                            . 'target="_blank"><i class="far fa-plus-square"></i>'
//                            . ' <i class="fas fa-external-link-alt"></i> ' . $i . '</a>';
//                    }
//                    $output .= '</footer></details>';
//                    break;
            }
        }
        return $output;
    }

    /**
     * Returns if a project-specific sections should be displayed in the administration.
     *
     * @deprecated 0.4.7 Set `$featureFlags['admin_latte_render'] = true;` instead.
     * @return bool
     */
    protected function projectSpecificSectionsCondition()
    {
        return
            isset($this->get['urls']) ||
            //F
            isset($this->get['divisions-products']) ||
            isset($this->get['translations']) ||
            //A
            isset($this->get['products']) ||
            isset($this->get['pages']);
    }

    /**
     * Output (in HTML) the project-specific admin sections
     * Usually only selects project specific section method that generates HTML
     *
     * @deprecated 0.4.7 Set `$featureFlags['legacy_admin_methods_instead_of_admin_models'] = true;` to use it.
     * @see AdminModels/PagesAdminModel.php
     * @see AdminModels/ProductsAdminModel.php
     * @return string
     */
    protected function projectSpecificSections()
    {
        //F
        $output = '';
        if (isset($this->get['divisions-products'])) {
            $output .= $this->sectionDivisionsProducts();
        } elseif (isset($this->get['translations'])) {
            $output .= $this->sectionTranslations();
        } elseif (isset($this->get['urls'])) {
            $output .= $this->sectionUrls();
        }

        //A
        if (!isset($_SESSION['user'])) { // TODO check if this is a redundant check
            return $output;
        }
        // products // TODO make work in Dist
        if (isset($this->get['products'])) {
            $output .= '<h1>' . $this->tableAdmin->translate('Products') . '</h1><div id="agenda-products">';
            $categories = $this->MyCMS->fetchAll('SELECT id,name_' . $_SESSION['language'] . ' AS category,active
                FROM `' . TAB_PREFIX . 'category`');
                // TODO reconsider code below from project A
                //. ' WHERE LENGTH(path)=' . (strlen($this->MyCMS->SETTINGS['PATH_CATEGORY']) + PATH_MODULE) .
                //' AND LEFT(path,' . PATH_MODULE . ')="' .
                //$this->MyCMS->escapeSQL($this->MyCMS->SETTINGS['PATH_CATEGORY']) . '"
                //ORDER BY path');
            $products = $this->MyCMS->fetchAndReindexStrictArray('SELECT category_id,id,name_'
                . $_SESSION['language'] . ' AS product,'
                //. 'image,' // TODO add image to the default dist app
                . 'sort,active FROM `' . TAB_PREFIX . 'product` ORDER BY sort');
            $perex = $this->MyCMS->fetchAndReindexStrictArray('SELECT '
                //. 'product_id,' //TODO was used in A project to link certain content table rows to products.
                // Reconsider here.
                . 'id,type,active,TRIM(CONCAT(content_'
                . $_SESSION['language'] . ', " ", CONCAT(LEFT(content_'
                . $_SESSION['language'] . ', 50), "…"))) AS content
                FROM `' . TAB_PREFIX . 'content` '
                . 'WHERE type IN ("perex", "claim", "testimonial") '
                //. 'AND product_id IS NOT NULL ' // TODO see product_id above
                . 'ORDER BY FIELD(type, "testimonial", "claim", "perex")');
            foreach ($categories as $category) {
                Assert::string($category['id']);
                Assert::string($category['category']);
                $output .= '<h4' . ($category['active'] == 1 ? '' : ' class="inactive"') . '><a href="?table='
                    . TAB_PREFIX . 'category&amp;where[id]=' . $category['id']
                    . '" title="' . $this->tableAdmin->translate('Edit') . '">'
                    . '<i class="fas fa-edit"></i></a> '
                    . '<button type="button" class="btn btn-sm d-inline category-switch" value="-1" data-id="'
                    . (int) $category['id'] . '" title="' . $this->tableAdmin->translate('Move up')
                    . '"><i class="fas fa-arrow-up"></i></button> '
                    . '<button type="button" class="btn btn-sm d-inline category-switch" value="1" data-id="'
                    . (int) $category['id'] . '" title="' . $this->tableAdmin->translate('Move down')
                    . '"><i class="fas fa-arrow-down"></i></button> '
                    . Tools::h($category['category'] ?: 'N/A') . '</h4>' . PHP_EOL;
                $productLine = isset($products[$category['id']]) ? (isset($products[$category['id']][0]) ?
                    $products[$category['id']] : [$products[$category['id']]]) : [];
                Assert::isArray($productLine);
                uasort($productLine, function ($a, $b) {
                    Assert::isArray($a);
                    Assert::isArray($b);
                    return $a['sort'] == $b['sort'] ? 0 : ($a['sort'] < $b['sort'] ? -1 : 1);
                });
                $i = 1;
                foreach ($productLine as $product) {
                    Assert::isArray($product);
                    if ($product['sort'] != $i) {
                        if (
                            $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'product SET sort=' . $i
                                . ' WHERE id=' . (int) $product['id'])
                        ) {
                            $product['sort'] = $i;
                        } else {
                            $this->MyCMS->logger->warning('No luck changing product order.'
                                . ' product id=' . $product['id']);
                        }
                    }
                    $tmp = isset($perex[$product['id']]) && is_array($perex[$product['id']]) ?
                        (isset($perex[$product['id']][0]) ? $perex[$product['id']] : [$perex[$product['id']]]) : [];
                    $output .= '<details class="ml-4' . ($product['active'] ? '' : ' inactive-item')
                        . '"><summary class="d-inline-block"><a href="?table=' . TAB_PREFIX . 'product&amp;where[id]='
                        . $product['id'] . '" title="' . $this->tableAdmin->translate('Edit')
                        . '"><i class="fas fa-edit"></i></a> '
                        . '<button type="button" class="btn btn-xs d-inline product-switch" data-id="'
                        . (int) $product['id'] . '" value="-1" title="' . $this->tableAdmin->translate('Move up')
                        . '"><i class="fas fa-arrow-up"></i></button> '
                        . '<button type="button" class="btn btn-xs d-inline product-switch" data-id="'
                        . (int) $product['id'] . '" value="1" title="' . $this->tableAdmin->translate('Move down')
                        . '"><i class="fas fa-arrow-down"></i></button>'
                        . '<span' . ($product['active'] ? '' : ' class="inactive"') . '> '
                        . Tools::h((string) $product['product']) . '</span>'
                        . ' <sup class="product-texts badge badge-' . (count($tmp) ? 'secondary' : 'warning')
                        . '"><small>' . count($tmp) . '</small></sup>'
                        // TODO implement image field
                        //. ' <sup class="product-images badge badge-' .
                        //(file_exists((string) $product['image']) ? 'secondary' : 'warning') .
                        //'" data-toggle="tooltip" data-html="true" title="<img src=\'' .
                        //Tools::h((string) $product['image']) .
                        //'\' width=\'200\' class=\'img-thumbnail\'/>"><i class="far fa-image"></i></sup>'
                        . '</summary>';
                    foreach ($tmp as $row) {
                        Assert::isArray($row);
                        $output .= '<div class="ml-5' . ($row['active'] ? '' : ' inactive') . '"><a href="?table='
                            . TAB_PREFIX . 'content&amp;where[id]=' . $row['id'] . '"><i class="fas fa-edit"></i></a> '
                            . '<sup>' . $row['type'] . '</sup> ' . Tools::h(strip_tags((string) $row['content']))
                            . '</div>' . PHP_EOL;
                    }
                    $output .= '<div class="ml-5"><a href="?table=' . TAB_PREFIX .
                        'content&amp;where[]=&amp;prefill[type]=perex&amp;prefill[product_id]=' . $product['id'] . '">'
                        . '<i class="far fa-plus-square"></i></a> ' . $this->tableAdmin->translate('New record')
                        . '</div>' . PHP_EOL
                        . '</details>' . PHP_EOL;
                    $i++;
                }
                $output .= '<a href="?table=' . TAB_PREFIX . 'product&amp;where[]=&amp;prefill[category_id]='
                    . $category['id'] . '&amp;prefill[sort]=' . $i . '" class="ml-4">'
                    . '<i class="far fa-plus-square"></i></a> ' . $this->tableAdmin->translate('New record');
            }
            $query = $this->MyCMS->dbms->queryStrictObject('SELECT id,name_' . $_SESSION['language']
                . ' AS product,sort,active FROM `' . TAB_PREFIX
                . 'product` WHERE category_id IN (0, NULL) ORDER BY sort');
            $output .= $query->num_rows ? '<h4><i>' . $this->tableAdmin->translate('None') . '</i></h4>' . PHP_EOL : '';
            while ($row = $query->fetch_assoc()) {
                $output .= '<a href="?table=' . TAB_PREFIX . 'product&amp;where[id]=' . $row['id'] .
                    '"><i class="fa fa-edit"></i></a> ' . Tools::h((string) $row['title']) . '<br />' . PHP_EOL;
            }
            $output .= '<footer>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-actives" title="'
                . $this->tableAdmin->translate('Toggle inactive') . '"><i class="far fa-eye-slash"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-texts" title="'
                . $this->tableAdmin->translate('Toggle number of texts') . '">#<i class="far fa-file"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-images" title="'
                . $this->tableAdmin->translate('Toggle image thumbnails') . '"><i class="far fa-image"></i></button>
                </footer></div>';
        } elseif (isset($this->get['pages'])) { // pages // TODO make it work in Dist
            $output .= '<h1>' . $this->tableAdmin->translate('Pages') . '</h1><div id="agenda-pages">';
            $categories = $this->MyCMS->fetchAndReindexStrictArray('SELECT id,'
                //. 'path,' // TODO path was used in A project. Reconsider here.
                . 'active,name_'
                . $_SESSION['language'] . ' AS category FROM `' . TAB_PREFIX . 'category`');
                // TODO path was used in A project. Reconsider here.
                // . ' WHERE LEFT(path, ' . PATH_MODULE . ')="'
                // . $this->MyCMS->escapeSQL($this->MyCMS->SETTINGS['PATH_HOME']) . '" ORDER BY path');
            //\Tracy\Debugger::barDump($categories, 'CATEGORIES'); // temp
            $articles = $this->MyCMS->fetchAndReindexStrictArray('SELECT '
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
            $articles = $this->MyCMS->fetchAndReindex('SELECT 0, id, IF(content_' . $_SESSION['language']
                . ' = "", LEFT(CONCAT(code, " ", content_' . $_SESSION['language'] . '), 100),'
                . ' content_' . $_SESSION['language'] . ') AS content
                FROM `' . TAB_PREFIX . 'content`');
                //. ' WHERE category_id IS NULL AND product_id IS NULL'); // used in project A - TODO reconsider
            if ($articles) {
                $output .= '<details><summary><tt>NULL</tt></summary>';
                if (
                    $tmp = $this->MyCMS->fetchAndReindex(
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
        }
        return $output;
    }

    /**
     * Called from projectSpecificSections
     * F code - TODO make work in Dist
     *
     * @deprecated 0.4.7 Set `$featureFlags['legacy_admin_methods_instead_of_admin_models'] = true;` to use it.
     * @see AdminModels/DivisionsProductsAdminModel.php
     * @return string
     */
    protected function sectionDivisionsProducts()
    {
        $output = '<h1>' . $this->tableAdmin->translate('Divisions and products') . '</h1><div id="agenda-products">';
        // TODO consider implementing from project F
        $divisions = $this->MyCMS->fetchAndReindexStrictArray('SELECT '
            // TODO TAB_PREFIX below instead of mycmsprojectspecific_
            . '* FROM mycmsprojectspecific_content LIMIT 0'); // always return empty set - replace by working code below
//            . 'id,division_' . $_SESSION['language'] .
//            ' AS division,' . ($tmp = 'sort+IF(id=' . Tools::set($_SESSION['division-switch'], 0) . ',' .
//            Tools::set($_SESSION['division-delta'], 0) . ',0)') . ' AS sort,active FROM `' . TAB_PREFIX .
//            'division` ORDER BY ' . $tmp);
        $parents = $this->MyCMS->fetchAll('SELECT '
//                . 'division_id,'
            . 'id,name_' . $_SESSION['language']
            . ' AS product,' . ($tmp = 'sort+IF(id=' . Tools::set($_SESSION['product-switch'], 0) . ','
            . Tools::set($_SESSION['product-delta'], 0) . ',0)') . ' AS sort,active FROM `' . TAB_PREFIX
            . 'product`'
            //. ' WHERE parent_product_id = 0'
            . ' ORDER BY '
            //. 'division_id,'
            . $tmp);
        $children = $this->MyCMS->fetchAll('SELECT '
//                . 'parent_product_id,'
            . 'id,name_' . $_SESSION['language']
            . ' AS product,' . ($tmp = 'sort+IF(id=' . Tools::set($_SESSION['product-switch'], 0) . ','
            . Tools::set($_SESSION['product-delta'], 0) . ',0)') . ' AS sort,active'
            . ' FROM ' . TAB_PREFIX . 'product'
            //. ' WHERE parent_product_id <> 0'
            . ' ORDER BY '
            //. 'parent_product_id,'
            . $tmp);
        $sort = array(0, 0, 0);
        $correctOrder = array();
        if (!empty($divisions)) {
            foreach ($divisions as $divisionId => $division) {
                Assert::isArray($division);
                $output .= '<details open><summary class="d-inline-block"><big'
                    . ($division['active'] == 1 ? '' : ' class="inactive"') . '><a href="?table=' . TAB_PREFIX
                    . 'division&amp;where[id]=' . $divisionId . '" title="' . $this->tableAdmin->translate('Edit')
                    . '">'
                    . '<i class="fa fa-edit" aria-hidden="true"></i></a> '
                    . '<button type="button" class="btn btn-sm d-inline" name="division-up" value="' . $divisionId
                    . '" title="' . $this->tableAdmin->translate('Move up') . '">'
                    . '<i class="fa fa-arrow-up" aria-hidden="true"></i></button> '
                    . '<button type="button" class="btn btn-sm d-inline mr-2" name="division-down" value="'
                    . $divisionId
                    . '" title="' . $this->tableAdmin->translate('Move down') . '">'
                    . '<i class="fa fa-arrow-down" aria-hidden="true"></i></button>'
                    . Tools::h($division['division'] ?: 'N/A') . '</big></summary>' . PHP_EOL;
                if (++$sort[0] != $division['sort']) {
                    $correctOrder[] = array($divisionId, $sort[0], false);
                }
                $sort[1] = 0;
                if (!empty($parents)) {
                    foreach ($parents as $parent) {
                        if ($parent['division_id'] == $divisionId) {
                            $output .= '<details class="ml-4"><summary class="d-inline-block"><a href="?table='
                                . TAB_PREFIX . 'product&amp;where[id]=' . $parent['id'] . '" target="_blank" title="'
                                . $this->tableAdmin->translate('Link will open in a new window')
                                . '"><i class="fa fa-external-link" aria-hidden="true"></i></a> '
                                . '<button type="button" class="btn btn-xs d-inline" name="product-up" value="'
                                . $parent['id'] . '" title="' . $this->tableAdmin->translate('Move up') . '">'
                                . '<i class="fa fa-arrow-up" aria-hidden="true"></i></button> '
                                . '<button type="button" class="btn btn-xs d-inline mr-2" name="product-down" value="'
                                . $parent['id'] . '" title="' . $this->tableAdmin->translate('Move down') . '">'
                                . '<i class="fa fa-arrow-down" aria-hidden="true"></i></button>';
                            $sort[1]++;
                            if ($sort[1] != $parent['sort']) {
                                $correctOrder[] = array($parent['id'], $sort[1]);
                            }
                            $sort[2] = 0;
                            $tmp = [];
                            if (!empty($children)) {
                                foreach ($children as $child) {
                                    if ($child['parent_product_id'] == $parent['id']) {
                                        Assert::string($child['product']);
                                        $tmp [] = '<div class="ml-4"><a href="?table='
                                            . TAB_PREFIX . 'product&amp;where[id]=' . $child['id']
                                            . '" target="_blank" title="' . $this->tableAdmin->translate('Edit')
                                            . '"><i class="fa fa-external-link" aria-hidden="true"></i></a> '
                                            . '<button type="button" class="btn btn-xs d-inline" '
                                            . 'name="product-up" value="'
                                            . $child['id'] . '" title="' . $this->tableAdmin->translate('Move up')
                                            . '"><i class="fa fa-arrow-up" aria-hidden="true"></i></button> '
                                            . '<button type="button" class="btn btn-xs d-inline mr-2" '
                                            . 'name="product-down" '
                                            . 'value="' . $child['id']
                                            . '" title="' . $this->tableAdmin->translate('Move down')
                                            . '"><i class="fa fa-arrow-down" aria-hidden="true"></i></button>'
                                            . Tools::h($child['product'])
                                            . '</div>';
                                        $sort[2]++;
                                        if ($sort[2] != $child['sort']) {
                                            $correctOrder[] = array($child['id'], $sort[2]);
                                        }
                                    }
                                }
                            }
                            $output .= '<span class="' . ($parent['active'] ? 'active' : 'inactive') . '">'
                                . Tools::h((string) $parent['product']) . '</span>'
                                . '<sup class="badge badge-secondary ml-1">' . count($tmp) . '</sup></summary>'
                                . implode(PHP_EOL, $tmp)
                                . '<a href="?table=' . TAB_PREFIX . 'product&amp;where[]=&amp;prefill[division_id]='
                                . $divisionId . '&amp;prefill[parent_product_id]=' . $parent['id']
                                . '&amp;prefill[sort]=' . $sort[1]
                                . '" class="ml-4"><i class="fa fa-plus-square-o" aria-hidden="true"></i></a> '
                                . $this->tableAdmin->translate('New record')
                                . '</details>' . PHP_EOL;
                        }
                    }
                }
                $output .= '<a href="?table=' . TAB_PREFIX . 'product&amp;where[]=&amp;prefill[division_id]='
                    . $divisionId . '&amp;prefill[sort]=' . $sort[0] . '" class="ml-4">'
                    . '<i class="fa fa-plus-square-o" aria-hidden="true"></i></a> '
                    . $this->tableAdmin->translate('New record') . '</summary></details>';
            }
        }
        $output .= '</div><form action="" method="post">'
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
            . '<button name="export-offline" type="submit" class="btn btn-sm invisible">Export off-line</button>'
            . '</form>';
        foreach ($correctOrder as $value) {
            $this->MyCMS->dbms->query('UPDATE `' . TAB_PREFIX . (count($value) == 3 ? 'division' : 'product')
                . '` SET sort = ' . $value[1] . ' WHERE id = ' . $value[0]);
        }
        unset(
            $_SESSION['division-switch'],
            $_SESSION['division-delta'],
            $_SESSION['product-switch'],
            $_SESSION['product-delta']
        );
        return $output;
    }

    /**
     * Displays table with all translations to be added and resaved
     * Called from projectSpecificSections
     *
     * @deprecated 0.4.7 Set `$featureFlags['legacy_admin_methods_instead_of_admin_models'] = true;` to use it.
     * @see MyCMS/AdminModels/TranslationsProductsAdminModel.php
     * @return string
     */
    protected function sectionTranslations()
    {
        $found = []; // translations found in latte templates
        foreach (glob('template/*.latte') as $file) {
            $tempFileContents = file_get_contents($file);
            Assert::string($tempFileContents);
            preg_match_all('~\{=("([^"]+)"|\'([^\']+)\')\|translate\}~i', $tempFileContents, $matches);
            $found = array_merge($found, $matches[2]);
        }
        $found = array_unique($found);
        $output = '<h1><i class="fa fa-globe"></i> ' . $this->tableAdmin->translate('Translations')
            . '</h1><div id="agenda-translations">'
            . '<form action="" method="post" onsubmit="return confirm(\''
            . $this->tableAdmin->translate('Are you sure?') . '\')">'
            . Tools::htmlInput('translations', '', 1, array('type' => 'hidden'))
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
            . Tools::htmlInput('old_name', '', '', array('type' => 'hidden', 'id' => 'old_name'))
            . '<table class="table table-striped"><thead><tr><th style="width:'
            . intval(100 / (count($this->MyCMS->TRANSLATIONS) + 1)) . '%">'
            . Tools::htmlInput('one', '', false, 'radio') . '</th>';
        $translations = $keys = [];
        $localisation = new L10n($this->prefixUiL10n, $this->MyCMS->TRANSLATIONS);
        foreach ($this->MyCMS->TRANSLATIONS as $key => $value) {
            $output .= "<th>$value</th>";
            $translations[$key] = $localisation->readLocalisation($key);
            $keys = array_merge($keys, array_keys($translations[$key]));
        }
        $output .= '</tr></thead><tbody>' . PHP_EOL;
        $keys = array_unique($keys);
        natcasesort($keys);
        foreach ($keys as $key) {
            $output .= '<tr><th>'
                . Tools::htmlInput('one', '', $key, array('type' => 'radio', 'class' => 'translation')) . ' '
                . Tools::h((string) $key) . '</th>';
            foreach ($this->MyCMS->TRANSLATIONS as $code => $value) {
                $output .= '<td>' . Tools::htmlInput(
                    "tr[$code][$key]",
                    '',
                    Tools::set($translations[$code][$key], ''),
                    ['class' => 'form-control form-control-sm', 'title' => "$code: $key"]
                ) . '</td>';
            }
            $output .= '</tr>' . PHP_EOL;
            if ($key = array_search($key, $found)) {
                unset($found[$key]);
            }
        }
        $output .= '<tr><td>' . Tools::htmlInput(
            'new[0]',
            '',
            '',
            array('class' => 'form-control form-control-sm', 'title' => $this->tableAdmin->translate('New record'))
        ) . '</td>';
        foreach ($this->MyCMS->TRANSLATIONS as $key => $value) {
            $output .= '<td>' . Tools::htmlInput(
                "new[$key]",
                '',
                '',
                ['class' => 'form-control form-control-sm',
                        'title' => $this->tableAdmin->translate('New record') . ' (' . $value . ')']
            ) . '</td>';
        }
        $output .= '</tr></tbody></table>
            <button name="translations" type="submit" class="btn btn-secondary"><i class="fa fa-save"></i> '
            . $this->tableAdmin->translate('Save') . '</button>
            <button name="delete" type="submit" class="btn btn-secondary" value="1"><i class="fa fa-dot-circle"></i>
            <i class="fa fa-trash"></i> ' . $this->tableAdmin->translate('Delete') . '</button>
            <fieldset class="d-inline-block position-relative"><div class="input-group" id="rename-fieldset">'
            . '<div class="input-group-prepend">
              <button class="btn btn-secondary" type="submit"><i class="fa fa-dot-circle"></i> '
            . '<i class="fa fa-i-cursor"></i> ' . $this->tableAdmin->translate('Rename') . '</button>
            </div>'
            . Tools::htmlInput('new_name', '', '', array('class' => 'form-control', 'id' => 'new_name'))
            . '</div></fieldset>
            </form></div>' . PHP_EOL;
        $output .= count($found) ? '<h2 class="mt-4">' .
            $this->tableAdmin->translate('Missing translations in templates') . '</h2><ul>' : '';
        foreach ($found as $value) {
            $output .= '<li><code>' . Tools::h($value) . '</code></li>' . PHP_EOL;
        }
        $output .= count($found) ? '</ul>' : '';
        return $output;
    }

    /**
     * Friendly URL: one place to set them all, identify duplicities
     * Called from projectSpecificSections
     *
     * @deprecated 0.4.7 Set `$featureFlags['legacy_admin_methods_instead_of_admin_models'] = true;` to use it.
     * @see MyCMS/AdminModels/UrlsProductsAdminModel.php
     * @return string
     */
    protected function sectionUrls()
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
        $langs = array_keys($this->MyCMS->TRANSLATIONS);
        // Todo queryStrictArray
        $query = $this->MyCMS->dbms->queryStrictNonEmptyArray(
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
                    $this->MyCMS->dbms->fetchAll("SELECT COUNT(url_$i) AS _count, url_$i AS url"
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
                        '="' . $this->MyCMS->escapeSQL((string) $url) . '"'
                    );
            }
            $query = $this->MyCMS->fetchAll(implode(" UNION\n", $sql));
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

    /**
     * As vendor folder has usually denied access from browser,
     * the content of the standard admin.css MUST be available through this method
     * @deprecated 0.4.7 Set `$featureFlags['admin_latte_render'] = true;` instead.
     *
     * @return string
     */
    public function getAdminCss()
    {
        //admin.css of the App is expected in the MyAdmin resources anyway, so no reason to duplicate it here
        //return parent::getAdminCss() . PHP_EOL . file_get_contents(__DIR__ . '/../styles/admin.css') . PHP_EOL;
        return parent::getAdminCss() . PHP_EOL;
    }

    /**
     * Add project specific titles
     * TODO: test the A inspiration
     *
     * @deprecated 0.4.7 Set `$featureFlags['admin_latte_render'] = true;` instead.
     * @return string
     */
    public function getPageTitle()
    {
        return parent::getPageTitle() ?:
            (
                isset($this->get['pages']) ? $this->tableAdmin->translate('Pages') :
            (
                isset($this->get['products']) ? $this->tableAdmin->translate('Products') :
            (
                isset($this->get['urls']) ? $this->tableAdmin->translate('URL') :
            ''
            )
            )
            );
    }
}

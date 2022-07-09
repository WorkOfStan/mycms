<?php

namespace WorkOfStan\mycmsprojectnamespace\AdminModels;

use GodsDev\Tools\Tools;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Products management
 * Used by Admin::controller()
 *
 * @author rejthar@stanislavrejthar.com
 */
class ProductsAdminModel
{
    use \Nette\SmartObject;

    /** @var LogMysqli */
    protected $dbms = null;
    /** @var LoggerInterface Logger SHOULD by available to the application using MyCMS */
    public $logger;
    /** @var MyTableAdmin */
    protected $tableAdmin;

    /**
     * Constructor, expects a Database connection
     * @param LogMysqli $dbms The Database object
     * @param MyTableAdmin $tableAdmin for the translate method
     * @param LoggerInterface $logger
     */
    public function __construct(LogMysqli $dbms, MyTableAdmin $tableAdmin, LoggerInterface $logger)
    {
        $this->dbms = $dbms;
        $this->tableAdmin = $tableAdmin;
        $this->logger = $logger;
    }

    /**
     *
     * @return string
     */
    public function htmlOutput()
    {
        $output = '';
        // products // TODO make work in Dist
            $output .= '<h1>' . $this->tableAdmin->translate('Products') . '</h1><div id="agenda-products">';
            $categories = $this->dbms->fetchAll('SELECT id,name_' . $_SESSION['language'] . ' AS category,active
                FROM `' . TAB_PREFIX . 'category`');
                // TODO reconsider code below from project A
                //. ' WHERE LENGTH(path)=' . (strlen($this->MyCMS->SETTINGS['PATH_CATEGORY']) + PATH_MODULE) .
                //' AND LEFT(path,' . PATH_MODULE . ')="' .
                //$this->MyCMS->escapeSQL($this->MyCMS->SETTINGS['PATH_CATEGORY']) . '"
                //ORDER BY path');
            $products = $this->dbms->fetchAndReindexStrictArray('SELECT category_id,id,name_'
                . $_SESSION['language'] . ' AS product,'
                //. 'image,' // TODO add image to the default dist app
                . 'sort,active FROM `' . TAB_PREFIX . 'product` ORDER BY sort');
            $perex = $this->dbms->fetchAndReindexStrictArray('SELECT '
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
                            $this->dbms->query('UPDATE ' . TAB_PREFIX . 'product SET sort=' . $i
                                . ' WHERE id=' . (int) $product['id'])
                        ) {
                            $product['sort'] = $i;
                        } else {
                            $this->logger->warning('No luck changing product order.'
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
            $query = $this->dbms->queryStrictObject('SELECT id,name_' . $_SESSION['language']
                . ' AS product,sort,active FROM `' . TAB_PREFIX
                . 'product` WHERE category_id IN (0, NULL) ORDER BY sort');
            $output .= $query->num_rows ? '<h4><i>' . $this->tableAdmin->translate('None') . '</i></h4>' . PHP_EOL : '';
            while ($row = $query->fetch_assoc()) {
                $output .= '<a href="?table=' . TAB_PREFIX . 'product&amp;where[id]=' . $row['id'] .
                    '"><i class="fa fa-edit"></i></a> ' . Tools::h($row['title']) . '<br />' . PHP_EOL;
            }
            $output .= '<footer>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-actives" title="'
                . $this->tableAdmin->translate('Toggle inactive') . '"><i class="far fa-eye-slash"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-texts" title="'
                . $this->tableAdmin->translate('Toggle number of texts') . '">#<i class="far fa-file"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary" id="products-images" title="'
                . $this->tableAdmin->translate('Toggle image thumbnails') . '"><i class="far fa-image"></i></button>
                </footer></div>';

        Assert::string($output);
        return $output;
    }
}

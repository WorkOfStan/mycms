<?php

namespace WorkOfStan\mycmsprojectnamespace\AdminModels;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\MyTableAdmin;

/**
 * Divisions and Products management
 * Used by Admin::controller()
 *
 * @author rejthar@stanislavrejthar.com
 */
class DivisionsProductsAdminModel
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
     * TODO make work in Dist (based on F code)
     *
     * @return string
     */
    public function htmlOutput()
    {
        $output = '<h1>' . $this->tableAdmin->translate('Divisions and products') . '</h1><div id="agenda-products">';
        // TODO consider implementing from project F
        $divisions = $this->dbms->fetchAndReindexStrictArray('SELECT '
            // TODO TAB_PREFIX below instead of mycmsprojectspecific_
            . '* FROM mycmsprojectspecific_content LIMIT 0'); // always return empty set - replace by working code below
//            . 'id,division_' . $_SESSION['language'] .
//            ' AS division,' . ($tmp = 'sort+IF(id=' . Tools::set($_SESSION['division-switch'], 0) . ',' .
//            Tools::set($_SESSION['division-delta'], 0) . ',0)') . ' AS sort,active FROM `' . TAB_PREFIX .
//            'division` ORDER BY ' . $tmp);
        $parents = $this->dbms->fetchAll('SELECT '
//                . 'division_id,'
            . 'id,name_' . $_SESSION['language']
            . ' AS product,' . ($tmp = 'sort+IF(id=' . Tools::set($_SESSION['product-switch'], 0) . ','
            . Tools::set($_SESSION['product-delta'], 0) . ',0)') . ' AS sort,active FROM `' . TAB_PREFIX
            . 'product`'
            //. ' WHERE parent_product_id = 0'
            . ' ORDER BY '
            //. 'division_id,'
            . $tmp);
        $children = $this->dbms->fetchAll('SELECT '
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
            $this->dbms->query('UPDATE `' . TAB_PREFIX . (count($value) == 3 ? 'division' : 'product')
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
}

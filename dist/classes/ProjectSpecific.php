<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\ProjectCommon;
use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;

/**
 * functions specific to the project
 */
class ProjectSpecific extends ProjectCommon
{
    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /**
     * Search for specified text in the database, return results
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param string $text being searched for
     * @param int $offset
     * @param string $totalRows //TODO or?? mixed $totalRows first selected row
     *     (or its first column if only one column is selected),
     *     null on empty SELECT, or false on error
     * @return array search result
     */
    public function searchResults($text, $offset = 0, &$totalRows = null)
    {
        $result = [];
        $q = preg_quote($text);
        $query = $this->MyCMS->dbms->query('SELECT CONCAT("?article&id=", id) AS link,content_' . $this->language
            . ' AS title,LEFT(description_' . $this->language . ',1000) AS description
            FROM ' . TAB_PREFIX . 'content WHERE active="1" AND type IN ("page", "news") AND (content_'
            . $this->language . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT CONCAT("?category&id=", id) AS link,category_' . $this->language . ' AS title,LEFT(description_'
            . $this->language . ',1000) AS description
            FROM ' . TAB_PREFIX . 'category WHERE active="1" AND (category_' . $this->language
            . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT CONCAT("?product=", id) AS link,product_' . $this->language
            . ' AS title,LEFT(description_' . $this->language . ',200) AS description
            FROM ' . TAB_PREFIX . 'product WHERE product_' . $this->language
            // TODO Expected at least 1 space after "+"; 0 found ask CRS2
            . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%"
            LIMIT 10 OFFSET ' . +$offset);
        $totalRows = $this->MyCMS->fetchSingle('SELECT FOUND_ROWS()');
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $row['description'] = strip_tags(preg_replace('~(</[a-z0-9]+>)~six', "$1 ", $row['description']));
                if ($pos = mb_strpos(mb_strtolower($row['description']), mb_strtolower($text))) {
                    $row['description'] = mb_substr($row['description'], max(0, $pos - 20), 201);
                }
                $result [] = $row;
            }
        }
        return $result;
    }

    /**
     * Fetch from database details of content of given id/code
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param mixed $id of the content OPTIONAL
     * @param string $code OPTIONAL
     * @param array $options OPTIONAL
     * @return array resultset
     */
    public function getContent($id = null, $code = null, array $options = [])
    {
        $result = [];
        if (
            (!is_null($id) || !is_null($code)) && ($result = $this->MyCMS->fetchSingle('SELECT co.id,'
            . ' product_id,'
            . ' type,'
            . ' co.code,'
            . ' co.added,'
            . ' co.context,'
            . ' category_id,'
            . ' path,'
            . ' co.content_' . $options['language'] . ' AS title,'
            . ' co.perex_' . $options['language'] . ' AS perex,'
            . ' co.description_' . $options['language'] . ' AS description '
            . ' FROM ' . TAB_PREFIX . 'content co LEFT JOIN ' . TAB_PREFIX . 'category ca ON co.category_id=ca.id '
            . ' WHERE co.active="1"' . Tools::wrap($this->MyCMS->escapeSQL($code), ' AND co.code="', '"')
                . Tools::wrap(intval($id), ' AND co.id=') . ' LIMIT 1'))
        ) {
            $result['context'] = json_decode($result['context'], true) ?: [];
            $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        }
        $options += array('path' => $result['path'], 'except_id' => $result['id']);
        if (($pos = strpos($result['description'], '%CHILDREN%')) !== false) {
            $result['description'] = str_replace('%CHILDREN%', '', $result['description']);
            $this->MyCMS->context['children'] = ProjectSpecific::getChildren($result['category_id'], $options);
        }/* elseif (($pos = strpos($result['description'], '%GRANDCHILDREN%')) !== false) {
          $result['description'] = str_replace('%GRANDCHILDREN%', '', $result['description']);
          $this->MyCMS->context['children'] = ProjectSpecific::getChildren(
            $result['category_id'], $options + array('level' => 1));
          } */
        if (($pos = strpos($result['description'], '%SITEMAP%')) !== false) {
            $result['description'] = str_replace(
                '%SITEMAP%',
                ProjectSpecific::getSitemap($options),
                $result['description']
            );
        }
        return $result;
    }

    /**
     * Fetch from database details of category of given id/code
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param mixed $id of the content OPTIONAL
     * @param string $code OPTIONAL
     * @param array $options OPTIONAL
     * @return array resultset
     */
    public function getCategory($id = null, $code = null, array $options = [])
    {
        $result = $this->MyCMS->fetchSingle(
            'SELECT id AS category_id, ' // . 'path,'
            . ' context,'
            // . ' "page" AS type,'
            . ' added,'
            . ' name_' . $options['language'] . ' AS title,'
            . ' content_' . $options['language'] . ' AS description'
            . ' FROM ' . TAB_PREFIX . 'category WHERE active="1"'
            . Tools::wrap(
                $this->MyCMS->escapeSQL($code),
                ' AND code="',
                '"'
            ) . Tools::wrap(intval($id), ' AND id=') . ' LIMIT 1'
        );
        if ((!is_null($id) || !is_null($code)) && $result) {
            $result['context'] = json_decode($result['context'], true) ?: [];
            $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        }
        return $result;
    }

    /**
     * Retrieves product info
     *
     * @param int $id
     * @return array|null array first selected row, null on empty SELECT
     * @throws \Exception on error
     */
    public function getProduct($id)
    {
        Assert::integer($id, 'product MUST be identified by id');
        $result = $this->MyCMS->fetchSingle(
            'SELECT id,'
            . 'context,'
            . 'category_id,'
            . ' name_' . $this->language . ' AS title,'
            . ' content_' . $this->language . ' AS description '
            // TODO: Note: takto se do pole context[product] přidá field [link], který obsahuje potenciálně
            // friendly URL, ovšem relativní, tedy bez jazyka.
            // Je to příprava pro forced 301 SEO a pro hreflang funkcionalitu.
            . ',' . $this->getLinkSql('?product&id=', $this->language)
            . ' FROM ' . TAB_PREFIX . 'product WHERE active="1" AND'
            . ' name_' . $this->language . ' NOT LIKE "" AND' // hide product language variants with empty title
            . ' id=' . intval($id) . ' LIMIT 1'
        );
        if (!is_null($result)) {
            $result['context'] = json_decode($result['context'], true) ?: [];
        }
        return $result;
    }

    /**
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param string $path
     * @param array $options OPTIONAL
     * @return array|false
     */
    public function getBreadcrumbs($path, array $options = [])
    {
        $result = [];
        if ($path) {
            for ($i = 0, $l = strlen($path), $sql = ''; $i < $l; $i += PATH_MODULE) {
                $sql .= ',"' . $this->MyCMS->escapeSQL(substr($path, 0, $i + PATH_MODULE)) . '"';
            }
            $result = $this->MyCMS->fetchAndReindex(
                $sql = 'SELECT id,category_' . $options['language']
                . ' AS category FROM ' . TAB_PREFIX . 'category WHERE active="1" AND path IN (' . substr($sql, 1) . ')'
            );
        }
        return $result;
    }

    /**
     * Get "children" content to a specified category/ies
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param int|array<int> $category_id category/ies to search, either int or array of integers
     * @param array $options = []
     *          [path] - path of the category
     *          [level] - level to which seek for children
     *          [except_id] - id of the child to omit from the result
     * @return array|false - either associative array, empty array on empty SELECT, or false on error
     */
    public function getChildren($category_id, array $options = [])
    {
        Tools::setifnotset($options['level'], 0);
        if ($options['level'] && Tools::nonzero($options['path'])) {
            $category_id = array_keys($this->MyCMS->fetchAndReindex($sql = 'SELECT id FROM ' . TAB_PREFIX . 'category
                WHERE LEFT(path, ' . strlen($options['path']) . ')="' . $this->MyCMS->escapeSQL($options['path']) . '"
                AND LENGTH(path) > ' . strlen($options['path']) . '
                AND LENGTH(path) <= ' . (strlen($options['path']) + $options['level'] * PATH_MODULE)));
        } else {
            $category_id = array($category_id);
        }
        $result = $this->MyCMS->fetchAndReindex('SELECT co.id, image,code, CONCAT("?article&id=", co.id) AS link,
            content_' . $options['language'] . ' AS title,
            perex_' . $options['language'] . ' AS description
            FROM ' . TAB_PREFIX . 'content co LEFT JOIN ' . TAB_PREFIX . 'category ca ON co.category_id=ca.id
            WHERE co.active="1" AND category_id IN(' . Tools::arrayListed($category_id, 8) . ')'
            . Tools::wrap(Tools::setifnull($options['except_id']), ' AND co.id<>'));
        return $result;
    }

    /**
     * TODO: make this method useful for dist project as a demonstration
     *
     * @param array $options OPTIONAL
     * @return string
     */
    public function getSitemap(array $options = [])
    {
        $pages = $this->MyCMS->fetchAndReindex('SELECT path,id,category_' . $options['language']
            . ' AS category,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . PATH_MODULE . ')="'
            . $this->MyCMS->escapeSQL($options['PATH_HOME']) . '" ORDER BY path');
        $result = '';
        foreach ($pages as $key => $value) {
            $result .= '<div class="indent-' . (strlen($key) / PATH_MODULE - 1) . '">'
                . '<a href="?category&amp;id=' . $value['id'] . '">' . Tools::h($value['category']) . '</a>'
                . '</div>' . PHP_EOL;
        }
        return '<div class="sitemap">' . $result . '</div>';
    }
    /**
     * If there is no function at all in this class, PHPSTAN would return errors that cannot be hidden:
     * Class WorkOfStan\Stockpiler\ProjectSpecific extends unknown class GodsDev\MyCMS\ProjectCommon.
     * Class WorkOfStan\Stockpiler\ProjectSpecific uses unknown trait Nette\SmartObject.
     *
     * If any function exists it returns an error that can be put in ignoreErrors section of phpstan.neon:
     * Reflection error: GodsDev\MyCMS\ProjectCommon not found.
     *
     * TODO: consider PR for phpstan project
     *
     * @param string $param
     * @return string
     */
//    private function mockFunc($param)
//    {
//        return $param;
//    }
}

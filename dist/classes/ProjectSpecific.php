<?php

namespace WorkOfStan\mycmsprojectnamespace;

use Exception;
use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\ProjectCommon;

//use function WorkOfStan\MyCMS\ThrowableFunctions\preg_replaceString;

/**
 * Functions specific to the project (that are not in its own model)
 * (Last MyCMS/dist revision: 2022-03-06, v0.4.6+)
 */
class ProjectSpecific extends ProjectCommon
{
    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /**
     * Search for specified text in the database, return results
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param string $text being searched for
     * @param int $offset
     * @param int $limit
     * @param string $totalRows //TODO or?? mixed $totalRows first selected row
     *     (or its first column if only one column is selected),
     *     null on empty SELECT, or false on error
     * @return array<array<string>> search result
     */
    public function searchResults($text, $offset = 0, $limit = 10, &$totalRows = null)
    {
        $result = [];
        $q = preg_quote($text);
//        if (!ELASTICSEARCH) {
        $query = $this->MyCMS->dbms->queryStrictObject('SELECT '
            . 'CONCAT("?article&id=", id) AS link' // for FriendlyUrl refactor with $this->getLinkSql
            . ',content_' . $this->language . ' AS title,LEFT(description_' . $this->language . ',1000) AS description
            FROM `' . TAB_PREFIX . 'content` WHERE active="1" AND type IN ("page", "news") AND (content_'
            . $this->language . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT '
            . 'CONCAT("?category&id=", id) AS link' // for FriendlyUrl refactor with $this->getLinkSql
            . ',category_' . $this->language . ' AS title,LEFT(description_'
            . $this->language . ',1000) AS description
            FROM `' . TAB_PREFIX . 'category` WHERE active="1" AND (category_' . $this->language
            . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT '
            . 'CONCAT("?product=", id) AS link' // for FriendlyUrl refactor with $this->getLinkSql
            . ',product_' . $this->language
            . ' AS title,LEFT(description_' . $this->language . ',200) AS description
            FROM `' . TAB_PREFIX . 'product` WHERE product_' . $this->language
            // TODO Expected at least 1 space after "+"; 0 found ask CRS2
            . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%"
            LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
        $totalRows = $this->MyCMS->fetchSingle('SELECT FOUND_ROWS()');
        while ($row = $query->fetch_assoc()) {
            /* Orig code
              $row['description'] = strip_tags(preg_replaceString('~(</[a-z0-9]+>)~six', "$1 ", $row['description']));
              if ($pos = mb_strpos(mb_strtolower($row['description']), mb_strtolower($text))) {
              $row['description'] = mb_substr($row['description'], max(0, $pos - 20), 201);
              }
             *
             */
            //quick fix
            $row['description'] = str_replace(
                '&amp;',
                '&',
                str_replace('&nbsp;', ' ', strip_tags($row['description']))
            );
            $result [] = $row;
        }
        return $result;
//        }
//        //ELASTICSEARCH as used in F project - code below requires class Search extends MyCommon
//        $search = new Search($this->MyCMS, [
//            'elasticsearchParams' => $this->elasticsearchParams,
//            'contentQueries' => $this->contentQueriesSearch,
//            'elasticsearchIndex' => $this->elasticsearchIndex,
//        ]);
//        $searchResult = $search->search($q, $this->language);
//        $resultHits = $searchResult['hits']['hits'];
//        if (!is_array($resultHits)) {
//            $this->MyCMS->logger->alert('Is Elasticsearch active?');
//        }
//        $totalRows = count($resultHits);
//        foreach ($resultHits as $resultId => $resultRow) {
//            if (isset($resultRow['highlight'])) {
//                $tempFirstField = reset($resultRow['highlight']);
//                $tempContent = reset($tempFirstField);
//            } else {
//                $tempContent = '';
//            }
//            if (empty($tempContent)) {
//                $tempContent = mb_substr(
//                    $this->getFirstValue($resultRow['_source'], ['perex_cs', 'perex_en', 'intro_cs', 'intro_en',
//                                'content_cs', 'content_en', 'description_cs', 'description_en']),
//                    0,
//                    1000
//                );
//            }
//            $result[] = array(
//                'score' => $resultRow['_score'],
//                'title' => $this->getFirstValue($resultRow['_source'],
//                   ['page_cs', 'product_cs', 'page_en', 'product_en']),
//                'content' => $tempContent,
//                'url' => $resultRow['_source']['url'],
//            );
//        }
//        return array_slice($result, $offset, $limit);
    }

    /**
     * Fetch from database details of content of given id/code
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param mixed $id of the content OPTIONAL
     * @param string $code OPTIONAL
     * @param array<string> $options OPTIONAL
     * @return array<array<int|string>|string> resultset
     */
    public function getContent($id = null, $code = null, array $options = [])
    {
        if (is_null($id) && is_null($code)) {
            return [];
        }
        $result = $this->MyCMS->fetchSingle('SELECT co.id,'
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
            . ' FROM `' . TAB_PREFIX . 'content` co LEFT JOIN `' . TAB_PREFIX . 'category` ca ON co.category_id=ca.id '
            . ' WHERE co.active="1"'
            . (is_null($code) ? '' : Tools::wrap($this->MyCMS->escapeSQL($code), ' AND co.code="', '"'))
            . (is_null($id) ? '' : Tools::wrap(intval($id), ' AND co.id=')) .
            ' LIMIT 1');
        Assert::isArray($result);
        $result['context'] = json_decode((string) $result['context'], true) ?: [];
        $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        Assert::string($result['path']);
        Assert::string($result['id']);
        $options += array('path' => $result['path'], 'except_id' => $result['id']);
        Assert::string($result['description']);
        if (($pos = strpos($result['description'], '%CHILDREN%')) !== false) {
            $result['description'] = str_replace('%CHILDREN%', '', $result['description']);
            Assert::integer($result['category_id']);
            $this->MyCMS->context['children'] = self::getChildren($result['category_id'], $options);
        }/* elseif (($pos = strpos($result['description'], '%GRANDCHILDREN%')) !== false) {
          $result['description'] = str_replace('%GRANDCHILDREN%', '', $result['description']);
          $this->MyCMS->context['children'] = ProjectSpecific::getChildren(
          $result['category_id'], $options + array('level' => 1));
          } */
        if (($pos = strpos($result['description'], '%SITEMAP%')) !== false) {
            $result['description'] = str_replace(
                '%SITEMAP%',
                self::getSitemap($options),
                $result['description']
            );
        }
        Assert::string($result['description']);
        /**
         * @phpstan-ignore-next-line should return array<array<int|string>|string> but returns array
         */
        return $result;
    }
    //public function processProductDescription($description, array $options) // in A project

    /**
     * Fetch from database details of category of given id/code
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param mixed $id of the content OPTIONAL
     * @param string $code OPTIONAL
     * @param array<string> $options OPTIONAL
     * @return array<string>|null resultset
     */
    public function getCategory($id = null, $code = null, array $options = [])
    {
        $result = $this->MyCMS->dbms->fetchStringArray(
            'SELECT id AS category_id, ' // . 'path,'
            . ' context,'
            // . ' "page" AS type,'
            . ' added,'
            . ' name_' . $options['language'] . ' AS title,'
            . ' content_' . $options['language'] . ' AS description'
            . ' FROM `' . TAB_PREFIX . 'category` WHERE active="1"'
            . (is_null($code) ? '' : Tools::wrap($this->MyCMS->escapeSQL($code), ' AND code="', '"'))
            . (is_null($id) ? '' : Tools::wrap(intval($id), ' AND id=')) . ' LIMIT 1'
        );
        if ((!is_null($id) || !is_null($code)) && $result) {
            $result['context'] = json_decode((string) $result['context'], true) ?: [];
            $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        }
        /**
         * @phpstan-ignore-next-line should return array<string>|null but returns array|null
         */
        return $result;
    }

    /**
     * Retrieves product info
     *
     * @param int $id
     * @return array<string|null>|null array first selected row, null on empty SELECT
     */
    public function getProduct($id)
    {
        Assert::integer($id, 'product MUST be identified by id');
        $result = $this->MyCMS->dbms->fetchStringArray(
            'SELECT id,'
            . 'context,'
            . 'category_id,'
            . ' name_' . $this->language . ' AS title,'
            . ' content_' . $this->language . ' AS description '
            // TODO: Note: takto se do pole context[product] přidá field [link], který obsahuje potenciálně
            // friendly URL, ovšem relativní, tedy bez jazyka.
            // Je to příprava pro forced 301 SEO a pro hreflang funkcionalitu.
            . ',' . $this->getLinkSql('?product&id=', $this->language)
            . ' FROM `' . TAB_PREFIX . 'product` WHERE active="1" AND'
            . ' name_' . $this->language . ' NOT LIKE "" AND' // hide product language variants with empty title
            . ' id=' . intval($id) . ' LIMIT 1'
        );
        if (!is_null($result)) {
            $result['context'] = json_decode($result['context'], true) ?: [];
        }
        /**
         * @phpstan-ignore-next-line should return array<string|null>|null but returns array|null
         */
        return $result;
    }

    /**
     * Generates array of url=>label for breadcrumbs on pages
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param string $path
     * @return array<array<string>|string>|false
     *       xxx  array<array<array<null|string>|null|string>|string>|false
     */
    public function getBreadcrumbs($path)
    {
        if (!$path) {
            return [];
        }
        for ($i = 0, $l = strlen($path), $sql = ''; $i < $l; $i += PATH_MODULE) {
            $sql .= ',"' . $this->MyCMS->escapeSQL(substr($path, 0, $i + PATH_MODULE)) . '"';
        }
        $result = $this->MyCMS->fetchAndReindex('SELECT '
                . $this->getLinkSql("?category&id=", $this->language)
                . ' ,category_' . $this->language . ' AS category FROM `' . TAB_PREFIX . 'category`'
                . ' WHERE active="1" AND path IN (' . substr($sql, 1) . ')');
        // fix of PHPStan: should return array<array<string>|string>|false
        // but returns array<array<array<string|null>|string|null>|string>
        if ($result === false) {
            return false;
        }
        Assert::isArray($result);
        foreach ($result as $field => $row) {
            if (is_array($row)) {
                $result2[$field] = [];
                foreach ($row as $f => $str) {
                    Assert::string($str);
                    $result2[$field][$f] = (string) $str;
                }
            } else {
                Assert::string($row);
                $result2[$field] = (string) $row;
            }
        }
        /**
         * @phpstan-ignore-next-line should return array<array<string>|string>|false but returns non-empty-array
         */
        return $result2;
    }

    /**
     * Get "children" content to a specified category/ies
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param int|array<int> $category_id category/ies to search, either int or array of integers
     * @param array<string> $options = []
     *          [path] - path of the category
     *          [level] - level to which seek for children
     *          [except_id] - id of the child to omit from the result
     * @return array<array<string|int>|string|int>|false
     *          either associative array, empty array on empty SELECT, or false on error
     */
    public function getChildren($category_id, array $options = [])
    {
        Tools::setifnotset($options['level'], 0);
        if ($options['level'] && Tools::nonzero($options['path'])) {
            $tempKeys = $this->MyCMS->fetchAndReindex('SELECT id FROM `' . TAB_PREFIX . 'category`
                WHERE LEFT(path, ' . strlen($options['path']) . ')="' . $this->MyCMS->escapeSQL($options['path']) . '"
                AND LENGTH(path) > ' . strlen($options['path']) . '
                AND LENGTH(path) <= ' . (strlen($options['path']) + (int) $options['level'] * PATH_MODULE));
            Assert::notFalse($tempKeys);
            $category_id = array_keys($tempKeys);
        } else {
            $category_id = [$category_id];
        }
        /**
         * @phpstan-ignore-next-line should return array<array<int|string>|int|string>|false
         * but returns array<array<array<string|null>|string|null>|string>|false
         */
        return $this->MyCMS->fetchAndReindex('SELECT co.id, image,code, '
                . $this->getLinkSql("?article&id=", $this->language) . ' ,
            content_' . $this->language . ' AS title,
            perex_' . $this->language . ' AS description
            FROM `' . TAB_PREFIX . 'content` co LEFT JOIN `' . TAB_PREFIX . 'category` ca ON co.category_id=ca.id
            WHERE co.active="1" AND category_id IN(' . Tools::arrayListed($category_id, 8) . ')'
                . Tools::wrap(Tools::setifnull($options['except_id']), ' AND co.id<>'));
    }

    /**
     * TODO: make this method useful for dist project as a demonstration (inspired by A project)
     *
     * @param array<string> $options OPTIONAL (fields language and PATH_HOME expected)
     * @return string
     * @throws Exception if sitemap retrieval fails
     */
    public function getSitemap(array $options = [])
    {
        $pages = $this->MyCMS->fetchAndReindex('SELECT path,id,category_' . $options['language']
            . ' AS category,path FROM `' . TAB_PREFIX . 'category` WHERE LEFT(path, ' . PATH_MODULE . ')="'
            . $this->MyCMS->escapeSQL($options['PATH_HOME']) . '" ORDER BY path');
        if ($pages === false) {
            throw new Exception('Sitemap retrieval failed.');
        }
        $result = '';
        foreach ($pages as $key => $value) {
            Assert::isArray($value);
            Assert::string($value['id']);
            Assert::string($value['category']);
            $result .= '<div class="indent-' . (strlen($key) / PATH_MODULE - 1) . '">'
                . '<a href="?category&amp;id=' . $value['id'] . '">' . Tools::h($value['category']) . '</a>'
                . '</div>' . PHP_EOL;
        }
        return '<div class="sitemap">' . $result . '</div>';
    }
    /**
     * If there is no function at all in this class, PHPSTAN would return errors that cannot be hidden:
     * Class WorkOfStan\Stockpiler\ProjectSpecific extends unknown class WorkOfStan\MyCMS\ProjectCommon.
     * Class WorkOfStan\Stockpiler\ProjectSpecific uses unknown trait Nette\SmartObject.
     *
     * If any function exists it returns an error that can be put in ignoreErrors section of phpstan.neon:
     * Reflection error: WorkOfStan\MyCMS\ProjectCommon not found.
     *
     * TODO: consider PR for phpstan project
     * 220205: it seems that it is not needed anymore since PHPSTAN::1.4.5
     *
     * @param string $param
     * @return string
     */
//    private function mockFunc($param)
//    {
//        return $param;
//    }
}

<?php

namespace GodsDev\MYCMSPROJECTNAMESPACE;

use GodsDev\MyCMS\ProjectCommon;
use GodsDev\Tools\Tools;
use Assert\Assertion;

/**
 * functions specific to the project
 */
class ProjectSpecific extends ProjectCommon
{

    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var string */
    protected $language;

    /** Search for specified text in the database, return results
     * @param string text being searched for
     * @param int offset
     * @param type $totalRows
     * @return array search result
     */
    public function searchResults($text, $offset = 0, &$totalRows = null)
    {
        $result = array();
        $q = preg_quote($text);
        $query = $this->MyCMS->dbms->query('SELECT CONCAT("?article&id=", id) AS link,content_' . $this->language . ' AS title,LEFT(description_' . $this->language . ',1000) AS description
            FROM ' . TAB_PREFIX . 'content WHERE active="1" AND type IN ("page", "news") AND (content_' . $this->language . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT CONCAT("?category&id=", id) AS link,category_' . $this->language . ' AS title,LEFT(description_' . $this->language . ',1000) AS description
            FROM ' . TAB_PREFIX . 'category WHERE active="1" AND (category_' . $this->language . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%")
            UNION
            SELECT CONCAT("?product=", id) AS link,product_' . $this->language . ' AS title,LEFT(description_' . $this->language . ',200) AS description
            FROM ' . TAB_PREFIX . 'product WHERE product_' . $this->language . ' LIKE "%' . $q . '%" OR description_' . $this->language . ' LIKE "%' . $q . '%"
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
     * Fetch from database a content of given id
     * @param mixed $id of the content 
     * @return array resultset
     */
    public function getContent($id = null, $code = null, $options = array())
    {
        $result = array();
        if ((!is_null($id) || !is_null($code)) && ($result = $this->MyCMS->fetchSingle($sql = 'SELECT co.id,product_id,type,co.code,co.added,co.context,category_id,path,
            co.content_' . $options['language'] . ' AS title,
            co.perex_' . $options['language'] . ' AS perex,
            co.description_' . $options['language'] . ' AS description 
            FROM ' . TAB_PREFIX . 'content co LEFT JOIN ' . TAB_PREFIX . 'category ca ON category_id=ca.id 
            WHERE co.active="1"' . Tools::wrap($this->MyCMS->escapeSQL($code), ' AND co.code="', '"') . Tools::wrap(intval($id), ' AND co.id=') . ' LIMIT 1'))) {
            $result['context'] = json_decode($result['context'], true) ?: array();
            $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        }
        $options += array('path' => $result['path'], 'except_id' => $result['id']);
        if (($pos = strpos($result['description'], '%CHILDREN%')) !== false) {
            $result['description'] = str_replace('%CHILDREN%', '', $result['description']);
            $this->MyCMS->context['children'] = ProjectSpecific::getChildren($result['category_id'], $options);
        }/* elseif (($pos = strpos($result['description'], '%GRANDCHILDREN%')) !== false) {
          $result['description'] = str_replace('%GRANDCHILDREN%', '', $result['description']);
          $this->MyCMS->context['children'] = ProjectSpecific::getChildren($result['category_id'], $options + array('level' => 1));
          } */
        if (($pos = strpos($result['description'], '%SITEMAP%')) !== false) {
            $result['description'] = str_replace('%SITEMAP%', ProjectSpecific::getSitemap($options), $result['description']);
        }
        return $result;
    }

    /**
     * Fetch from database a category of given id
     * 
     * @param mixed $id of the content OPTIONAL
     * @param type $code OPTIONAL
     * @param array $options OPTIONAL
     * @return array resultset
     */
    public function getCategory($id = null, $code = null, array $options = array())
    {
        $result = array();
        if ((!is_null($id) || !is_null($code)) && ($result = $this->MyCMS->fetchSingle($sql = 'SELECT id AS category_id,path,context,"page" AS type,added,
            category_' . $options['language'] . ' AS title,
            description_' . $options['language'] . ' AS description
            FROM ' . TAB_PREFIX . 'category WHERE active="1"' . Tools::wrap($this->MyCMS->escapeSQL($code), ' AND code="', '"') . Tools::wrap(intval($id), ' AND id=') . ' LIMIT 1'))) {
            $result['context'] = json_decode($result['context'], true) ?: array();
            $result['added'] = Tools::localeDate($result['added'], $options['language'], false);
        }
        return $result;
    }

    /**
     * 
     * @param string $description
     * @param array $options
     * @return string
     */
    public function processProductDescription($description, array $options)
    {
        Assertion::string($description, "processProductDescription description not string");
        $result = '';
        $sections = explode('<hr>', $description); //<hr> vložená v CMS znamená, že se odrotuje další section s tím, že class photo se doplňuje class-ou produktu, aby se mohla měnit fotka dle produktu a pořadí
        $sectionCount = 0;
        foreach ($sections as $sectionKey => $section) { //@todo určitě jdou dát jen 3 a ty rotovat dokola, ale to můžeme pořešit později            
            $styleKey = $sectionKey % count($options['SECTION_STYLES']);
            $classes = $options['SECTION_STYLES'][$styleKey] . (($options['SECTION_STYLES'][$styleKey] == 'photo') ? " " . Tools::webalize($options['product'] . ' ' . $sectionKey) : '');
            $tempDiv = "\n<div data-aos=\"fade-up\" class=\"container\">\n{$section}\n</div>\n";
            $result .= ($options['hide_product_heading'] && ($sectionCount == 0))?$tempDiv: //when tiles are used, <div class="container"/> should not be within section because it spoils the size of tiles (might be fixed in CSS instead?)
                    ('<section class="' . trim($classes) . '" id="product-section-' . $sectionKey . '">'
                    . $tempDiv
                    . "</section>\n");
            $sectionCount++;                    
        }
        return $result;
    }

    /**
     * 
     * @param string $path
     * @param array $options OPTIONAL
     * @return mixed
     */
    public function getBreadcrumbs($path, array $options = array())
    {
        $result = array();
        if ($path) {
            for ($i = 0, $l = strlen($path), $sql = ''; $i < $l; $i += PATH_MODULE) {
                $sql .= ',"' . $this->MyCMS->escapeSQL(substr($path, 0, $i + PATH_MODULE)) . '"';
            }
            $result = $this->MyCMS->fetchAndReindex($sql = 'SELECT id,category_' . $options['language'] . ' AS category FROM ' . TAB_PREFIX . 'category WHERE active="1" AND path IN (' . substr($sql, 1) . ')');
        }
        return $result;
    }

    /**
     * 
     * @param type $category_id
     * @param array $options OPTIONAL
     * @return mixed
     */
    public function getChildren($category_id, array $options = array())
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
            WHERE co.active="1" AND category_id IN(' . Tools::arrayListed($category_id, 8) . ')' . Tools::wrap(Tools::setifnull($options['except_id']), ' AND co.id<>'));
        return $result;
    }

    /**
     * 
     * @param array $options OPTIONAL
     * @return string
     */
    public function getSitemap(array $options = array())
    {
        $pages = $this->MyCMS->fetchAndReindex('SELECT path,id,category_' . $options['language'] . ' AS category,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . PATH_MODULE . ')="' . $this->MyCMS->escapeSQL($options['PATH_HOME']) . '" ORDER BY path');
        $result = '';
        foreach ($pages as $key => $value) {
            $result .= '<div class="indent-' . (strlen($key) / PATH_MODULE - 1) . '"><a href="?category&amp;id=' . $value['id'] . '">' . Tools::h($value['category']) . '</a></div>' . PHP_EOL;
        }
        return '<div class="sitemap">' . $result . '</div>';
    }

}

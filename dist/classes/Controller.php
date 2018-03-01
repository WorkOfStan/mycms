<?php

namespace GodsDev\MYCMSPROJECTNAMESPACE;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyController;
use GodsDev\Tools\Tools;
use GodsDev\MYCMSPROJECTNAMESPACE\ProjectSpecific;
use Tracy\Debugger;

class Controller extends MyController
{

    use \Nette\SmartObject;

    //project specific accepted attributes:
    /** @var array */
    protected $sectionStyles;

    /** @var \GodsDev\MYCMSPROJECTNAMESPACE\ProjectSpecific */
    private $projectSpecific;

    /** @var string */
    protected $language = 'cs'; //default is Czech for MYCMSPROJECTSPECIFIC
    
    /**
     * Controller ascertain what the request is
     * 
     * Expect variables:
     * $MyCMS->template, context, logger, SETTINGS
     * $_SESSION
     * $_GET
     * $SECTION_STYLES
     * 
     * Expect constants:
     * PATH_MODULE
     * TAB_PREFIX
     *
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options overides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, array('language' => $this->language));
    }

    /**
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     * 
     * @return array
     */
    function controller()
    {
        $result = parent::controller();
        //@todo move language to __construct($options) incl. tests
        $language = $_SESSION['language'];

        //@todo refactor to use $result['template'] and ['context'] instead of $this->MyCMS->template and context ?
        $this->MyCMS->template = $result['template'];
        $this->MyCMS->context = $result['context'];
        $this->MyCMS->context['pageTitle'] = '';

        //@todo refactor to private methods to set general things and to get additional context variables
        //@todo refactor to use methods instead of direct db calls so that it is testable

        $this->MyCMS->context['categories'] = $this->MyCMS->fetchAndReindex('SELECT id,
            category_' . $language . ' AS category
            FROM ' . TAB_PREFIX . 'category 
            WHERE active="1" AND LEFT(path, ' . strlen($this->MyCMS->SETTINGS['PATH_CATEGORY']) . ')="' . $this->MyCMS->escapeSQL($this->MyCMS->SETTINGS['PATH_CATEGORY']) . '" AND LENGTH(path)=' . (strlen($this->MyCMS->SETTINGS['PATH_CATEGORY']) + PATH_MODULE) . ' ORDER BY path'
        ); // these are needed for the main menu and are condition for line page processing
        //
        //type is only for development
        if (isset($this->get['type']) && $this->get['type'] && in_array($this->get['type'], array('product', 'list', 'article_specimen', 'wizard'))) {
            $this->MyCMS->template = $this->get['type'];
        } else {
            $options = array('language' => $language, 'PATH_HOME' => $this->MyCMS->SETTINGS['PATH_HOME']);
            // prepare variables and set templates for each kind of request
            //search _GET[search] contains the search phrase
            if (isset($this->get['search']) && $this->get['search']) {
                $this->MyCMS->context['offset'] = isset($this->get['offset']) ? filter_var($this->get['offset'], FILTER_VALIDATE_INT, ['default' => 0, 'min_range' => 0, 'max_range' => 1e9]) : 0;
                $this->MyCMS->context['results'] = $this->projectSpecific->searchResults($this->get['search'], $this->MyCMS->context['offset'], $this->MyCMS->context['totalRows']);
                //@todo ošetřit empty result
                $this->MyCMS->template = 'search-results';
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Výsledky hledání');
            }
            //article - identified by _GET[id] (numerical) or _GET[code] (string)
            elseif (isset($this->get['article']) && (isset($this->get['id']) || isset($this->get['code']))) {
                if (isset($this->get['id'])) {
                    $this->get['id'] = filter_var($this->get['id'], FILTER_VALIDATE_INT, array('default' => 0, 'min_range' => 0, 'max_range' => 1e9));
                }
                $this->MyCMS->context['article'] = $this->projectSpecific->getContent(Tools::setifempty($this->get['id']), Tools::setifempty($this->get['code']), $options);
                if (is_null($this->MyCMS->context['article'])) {
                    $this->MyCMS->template = 'error404';
                    $this->MyCMS->logger->error("{$this->get['id']}/{$this->get['code']} missing");
                } else {
                    if (isset($this->MyCMS->context['article']['path'])) {
                        $this->MyCMS->context['breadcrumbs'] = $this->projectSpecific->getBreadcrumbs($this->MyCMS->context['article']['path'], $options);
                    }
                    $this->MyCMS->template = 'article';
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['article']['title'];
                }
            }
            //category get[category] && get[id] or get[code]
            elseif (isset($this->get['category']) && (isset($this->get['id']) || isset($this->get['code']))) {
                $options = array('language' => $language);
                if (isset($this->get['id'])) {
                    $this->get['id'] = filter_var($this->get['id'], FILTER_VALIDATE_INT, array('default' => 0, 'min_range' => 0, 'max_range' => 1e9));
                }
                $this->MyCMS->context['article'] = $this->projectSpecific->getCategory(Tools::setifempty($this->get['id']), Tools::setifempty($this->get['code']), $options);
                //use the same product desc layout for category descriptions
                $this->MyCMS->context['article']['description'] = $this->projectSpecific->processProductDescription($this->MyCMS->context['article']['description'], array(
                    'product' => $this->MyCMS->context['article']['title'],
                    'SECTION_STYLES' => $this->sectionStyles,
                    'hide_product_heading' => null
                ));
                $this->MyCMS->context['breadcrumbs'] = $this->projectSpecific->getBreadcrumbs($this->MyCMS->context['article']['path'], $options);
                $this->MyCMS->context['pageTitle'] = Tools::setifnull($this->MyCMS->context['article']['title']);
                if ($this->MyCMS->context['article']['path'] != $this->MyCMS->SETTINGS['PATH_HOME']) {
                    $this->MyCMS->template = 'article'; //category listing has the same template as the home page
                    $this->MyCMS->context['items'] = $this->MyCMS->fetchAll($sql = 'SELECT id, "line" AS type, CONCAT("?category&id=", id) AS link,image, 
                        category_' . $language . ' AS title, perex_' . $language . ' AS description
                        FROM ' . TAB_PREFIX . 'category 
                        WHERE active="1" AND (path LIKE "' . $this->MyCMS->escapeSQL($this->MyCMS->context['article']['path']) . str_repeat('_', PATH_MODULE) . '")
                        UNION
                        SELECT id, "article" AS type, CONCAT("?article&id=", id) AS link,image,
                        content_' . $language . ' AS title, perex_' . $language . ' AS description
                        FROM ' . TAB_PREFIX . 'content WHERE active="1" AND category_id=' . (int) $this->MyCMS->context['article']['category_id']
                    );
                    if (isset($this->MyCMS->context['article']['context']['fetch-depth']) && $this->MyCMS->context['article']['context']['fetch-depth'] == 2) {
                        $this->MyCMS->context['listType'] = 'rows';
                        foreach ($this->MyCMS->context['items'] as &$value) {
                            if ($value['type'] == 'line') {
                                // @todo: room for optimization
                                $value['items'] = $this->MyCMS->fetchAll($sql = 'SELECT id,"article" AS type, CONCAT("?article&id=", id) AS link,image,code,
                            content_' . $language . ' AS title, perex_' . $language . ' AS perex
                            FROM ' . TAB_PREFIX . 'content WHERE active="1" AND category_id=' . (int) $value['id']);
                            }
                        }
                    }
                } else {
                    $this->MyCMS->template = 'home';
                    unset($this->get['line']);
                }
            }
            //product _GET[product] = product id
            elseif (isset($this->get['product'])) {
                $this->get['product'] = filter_var($this->get['product'], FILTER_VALIDATE_INT);
                $this->MyCMS->context['product'] = $this->MyCMS->fetchSingle('SELECT id, context, category_id, image,
                    product_' . $language . ' AS product, 
                    description_' . $language . ' AS description
                    FROM ' . TAB_PREFIX . 'product WHERE active="1" AND id=' . intval($this->get['product']) . ' LIMIT 1'
                );
                if (is_null($this->MyCMS->context['product'])) {
                    $this->MyCMS->template = 'error404';
                } else {
                    $this->MyCMS->context['product']['context'] = json_decode($this->MyCMS->context['product']['context'],true);//decodes json so that product context may be used within template
                    $this->MyCMS->context['line'] = isset($this->MyCMS->context['product']['category_id']) ? $this->MyCMS->context['product']['category_id'] : null;
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['product']['product'] . (isset($this->MyCMS->context['categories'][$this->MyCMS->context['line']]) ? ' / ' . $this->MyCMS->context['categories'][$this->MyCMS->context['line']] : '');
                    $this->MyCMS->template = 'product';
                    // on product detail page, also get products from the same category (its id,name) 
                    $this->MyCMS->context['items'] = $this->MyCMS->fetchAll($sql='SELECT id,product_' . $_SESSION['language'] . ' AS title,category_id
                        FROM ' . TAB_PREFIX . 'product WHERE category_id=' . +$this->MyCMS->context['line'] . ' AND active="1" ORDER BY sort');
                    $localSectionStyles = isset($this->MyCMS->context['product']['context']["section_styles"]) ? explode(',', $this->MyCMS->context['product']['context']["section_styles"]) : null;
                    $this->MyCMS->context['product']['description'] = $this->projectSpecific->processProductDescription($this->MyCMS->context['product']['description'], array(
                        'product' => $this->MyCMS->context['product']['product'],
                        'SECTION_STYLES' => $localSectionStyles ? $localSectionStyles : $this->sectionStyles,
                        'hide_product_heading' => isset($this->MyCMS->context['product']['context']['hide_product_heading'])?true:false
                    ));
                }
            }

            // get additional context variables
            $this->MyCMS->context['line'] = isset($this->get['line']) && is_numeric($this->get['line']) ? $this->get['line'] : 0;
            if (isset($this->MyCMS->context['categories'][$this->MyCMS->context['line']])) {
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['categories'][$this->MyCMS->context['line']];
            } else {
                $this->MyCMS->context['line'] = null;
            }

            if ($this->MyCMS->context['line'] || $this->MyCMS->template == 'home') {
                if (isset($this->get['about'])) {//@todo keep about as a special page or make it instance of home with different content?
                    $this->MyCMS->context['slides'] = array();
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('O společnosti');
                    $this->MyCMS->context['items'] = $this->MyCMS->fetchAll('SELECT id, context, CONCAT("?category&id=", id) AS link, \'about\' AS type, image, 
                        category_' . $language . ' AS title,
                        perex_' . $language . ' AS description
                        FROM ' . TAB_PREFIX . 'category WHERE active="1"
                        AND `id` IN (13,14,11,18,20,21)
                        ORDER BY FIELD (`id`, 13,14,11,18,20,21)
                        LIMIT 10'
                    );
                    //hack of tile "certifikace"
//                    $this->MyCMS->context['items'][5]['link'] = '?article&id=71';
                } else {
                    $this->MyCMS->context['items'] = $this->MyCMS->fetchAll($sql = 'SELECT id, category_id, context, image, CONCAT("?product=", id) AS link,
                        product_' . $language . ' AS title
                        FROM ' . TAB_PREFIX . 'product WHERE active="1"' . Tools::wrap($this->MyCMS->context['line'], ' AND category_id=', '')
                        . ($this->MyCMS->context['line'] ? ' ORDER BY sort ASC ' : ' ORDER BY RAND()')
                        . ' LIMIT 10'
                    );
                    $descriptions = $this->MyCMS->fetchAndReindex('SELECT product_id, type, c.description_' . $language . ' AS description, c.image
                        FROM ' . TAB_PREFIX . 'content c LEFT JOIN ' . TAB_PREFIX . 'product p ON c.product_id=p.id
                        WHERE p.active="1" AND c.active="1" AND type IN ("testimonial", "claim", "perex")' . Tools::wrap($this->MyCMS->context['line'], ' AND p.category_id=') . '
                        ORDER BY product_id, FIELD(type, "testimonial", "claim", "perex"), RAND()'
                    );
                    $id = array();
                    foreach ($this->MyCMS->context['items'] as &$value) {
                        $id [] = $value['id'];
                        $value['type'] = isset($descriptions[$value['id']][0]) ? $descriptions[$value['id']][0]['type'] : (isset($descriptions[$value['id']]['type']) ? $descriptions[$value['id']]['type'] : null);
                        $value['type'] = $value['type'] == 'claim' ? 'perex' : $value['type']; //"claim" will be processed same as "perex"
                        $tmp = isset($descriptions[$value['id']][0]) ? $descriptions[$value['id']][0]['image'] : (isset($descriptions[$value['id']]) ? $descriptions[$value['id']]['image'] : '');
                        if (!file_exists($value['image'] = $tmp)) {
                            Debugger::barDump("image '{$value['image']}' to product '{$value['title']}' (id {$value['id']}) does not exist!");
                        }
                        $value['description'] = isset($descriptions[$value['id']]) ? $descriptions[$value['id']] : array('description' => '', 'type' => '');
                        $value['description'] = isset($value['description']['description']) ? $value['description']['description'] : reset($value['description'])['description'];
                        $value['context'] = json_decode($value['context'], true) or array();
                    }
                    if (!$this->MyCMS->context['line']) {
                        $this->MyCMS->context['slides'] = array_splice($this->MyCMS->context['items'], 7);
                    }
                }
            }
        }//typeArr

        if ($this->MyCMS->template === 'error404') {
            http_response_code(404);
        }

        return array(
            'template' => $this->MyCMS->template,
            'context' => $this->MyCMS->context,
        );
    }

    /**
     * For PHP Unit test
     * 
     * @return array
     */
    public function getVars()
    {
        return array("get" => $this->get, "session" => $this->session,
            "sectionStyles" => $this->sectionStyles
        );
    }

}

<?php

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\MyController;
use WorkOfStan\mycmsprojectnamespace\FriendlyUrl;
use WorkOfStan\mycmsprojectnamespace\Mail;
use WorkOfStan\mycmsprojectnamespace\ProjectSpecific;

/**
 * Controller (of MVC)
 * (Last MyCMS/dist revision: 2021-05-20, v0.4.0)
 */
class Controller extends MyController
{
    use \Nette\SmartObject;

    //project specific accepted attributes:

    /** @var string */
    protected $httpMethod;

    /** @var string */
    protected $language; // = DEFAULT_LANGUAGE;

    /** @var Mail */
    protected $mail;

    /** @var ProjectSpecific */
    private $projectSpecific;

    /** @var string */
    protected $requestUri; // = ''; // default is homepage

    /**
     * Feature flags that bubble down to latte and controller
     *
     * @var array<bool>
     */
    protected $featureFlags;

    /**
     * Bleeds information within determineTemplate method
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Controller ascertain what the request is
     *
     * Expect variables:
     * $MyCMS->template, context, logger, SETTINGS
     * $_SESSION
     * $_GET
     *
     * Expect constants:
     * PATH_MODULE
     * TAB_PREFIX
     *
     *
     * @param MyCMS $MyCMS
     * @param array<mixed> $options overrides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, array_merge($options, [
            'friendlyUrl' => new FriendlyUrl($MyCMS, $options), //$this->friendlyUrl instantiated
        ]));
        //Note: $this->featureFlags is populated
        if (class_exists('Swift_SmtpTransport')) { // so that PHPUnit test run from root doesn't fail
            $this->mail = new Mail($MyCMS, $options);
        }
    }

    /**
     * Processes $this->MyCMS->template after method prepareTemplate
     * Set $this->MyCMS->context accordingly for all (or multiple) pages
     * Might even change $this->MyCMS->template value
     * Contains the typical controller code
     *
     * @param array<mixed> $options
     * @return bool true on success, false on error
     */
    protected function prepareAllTemplates(array $options = [])
    {
        return true;
    }

    /**
     * Processes $this->MyCMS->template after method determineTemplate
     * Set $this->MyCMS->context accordingly for single templates
     * May even change $this->MyCMS->template value
     *
     * @param array<mixed> $options ['REQUEST_URI']
     * @return bool true on success, false on error
     */
    protected function prepareTemplate(array $options = [])
    {
        $this->verboseBarDump(
            ['template' => $this->MyCMS->template, 'language' => $this->language],
            'template used to prepareTemplate switch'
        );
        Assert::inArray($this->httpMethod, ['GET', 'POST', 'DELETE'], 'Unauthorized HTTP method %s');
        Debugger::barDump($this->httpMethod, 'REQUEST_METHOD');
        // language is already properly set, so set it to ProjectSpecific object
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, ['language' => $this->language]);
        switch ($this->MyCMS->template) {
            case self::TEMPLATE_DEFAULT:
                return true;
            case self::TEMPLATE_NOT_FOUND:
                return true;
            case 'article':
                if (array_key_exists('id', $this->get)) {
                    Assert::integer($this->get['id'], 'article MUST be identified by id');
                    $articleIdentifier = ' id=' . intval((int) $this->get['id']);
                } else {
                    Assert::keyExists($this->get, 'code', 'without id article MUST be identified by code');
                    Assert::string($this->get['code'], 'article code MUST be string');
                    $articleIdentifier = ' code LIKE "' . $this->MyCMS->escapeSQL($this->get['code']) . '"';
                }
                $this->MyCMS->context['content'] = $this->MyCMS->fetchSingle(
                    'SELECT id,'
                    . 'context,'
                    // . 'category_id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description '
                    // TODO: Note: takto se do pole context[product] přidá field [link],
                    // který obsahuje potenciálně friendly URL, ovšem relativní, tedy bez jazyka.
                    // Je to příprava pro forced 301 SEO a pro hreflang funkcionalitu.
                    . ',' . $this->projectSpecific->getLinkSql('?article&id=', $this->language)
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active="1" AND'
                    . ' type LIKE "article" AND'
                    . $articleIdentifier
                    . ' LIMIT 1'
                );
                if (is_null($this->MyCMS->context['content'])) {
                    $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
                    return true;
                }
                $this->MyCMS->context['content']['context'] = json_decode(
                    $this->MyCMS->context['content']['context'],
                    true
                ); //decodes json so that article context may be used within template
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['content']['title'];
                $this->MyCMS->context['content']['image'] = array_key_exists(
                    'image',
                    $this->MyCMS->context['content']['context']
                ) ? (string) $this->MyCMS->context['content']['context']['image'] : '';
                return true;
            case 'category':
                if (!Tools::ifset($this->get['category'])) {
                    $categoryId = null;
                    // TODO localize // TODO content element
                    $this->MyCMS->context['pageTitle'] = 'Categories';
                    // TODO localize perex for all categories // TODO content element
                    $this->MyCMS->context['content']['description'] = 'About all categories';
                } else {
                    $this->MyCMS->context['content'] = $this->projectSpecific->getCategory(
                        Tools::ifset($this->get['category']),
                        null,
                        ['language' => $this->language]
                    );
                    Debugger::barDump($this->MyCMS->context['content'], 'category');
                    if (is_null($this->MyCMS->context['content'])) {
                        $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
                        return true;
                    }
                    $categoryId = $this->MyCMS->context['content']['category_id'];
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['content']['title'];
                }
                // TODO add perex for categories and products from content
                $this->verboseBarDump($categoryId, 'categoryId');
                $this->MyCMS->context['limit'] = PAGINATION_LIMIT;
                $this->MyCMS->context['list'] = $this->MyCMS->dbms->queryArray(
                    is_null($categoryId) ?
                    // list categories
                    ('SELECT id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description,'
                    . ' added'
                    . ' FROM `' . TAB_PREFIX . 'category` WHERE `active` = 1 ORDER BY sort ASC') :
                    // list products within category
                    ('SELECT id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description,'
                    . ' added'
                    . ' FROM `' . TAB_PREFIX . 'product` WHERE `category_id` = ' . $categoryId . ' AND `active` = 1'
                    . ' AND name_' . $this->language . ' NOT LIKE ""' // hide product language variants with empty title
                    . ' ORDER BY sort ASC')
                );
                $this->MyCMS->context['totalRows'] = ($this->MyCMS->context['list'] === false) ? 0 :
                    count($this->MyCMS->context['list']);
                return true;
            case 'item-1':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 1';
                return true;
            case 'item-B':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 2';
                $this->MyCMS->context['mailStatus'] = 'Test mail init';
                $tempItemB = $this->MyCMS->dbms->queryArray('SELECT `added` FROM `' . TAB_PREFIX . 'content` '
                    . 'WHERE `active` = "1" AND `type` LIKE "counter" AND `code` LIKE "last_email_sent" '
                    . 'ORDER BY `added` DESC LIMIT 0,1');
                $tempItemWait = $this->MyCMS->dbms->queryArray('SELECT `added` FROM `' . TAB_PREFIX . 'content` '
                    . 'WHERE `active` = "1" AND `type` LIKE "counter" AND `code` LIKE "last_email_sent" '
                    . 'AND `added` < DATE_SUB(NOW(), INTERVAL 23 HOUR) '
                    . 'ORDER BY `added` DESC LIMIT 0,1');
                Debugger::barDump(
                    ['last_email_sent' => $tempItemB, 'last_email_sent_within_waiting_period' => $tempItemWait],
                    'Last email sent timestamps'
                );
                if (empty($tempItemB)) {
                    // Init
                    $tempItemB = $this->MyCMS->dbms->query(
                        "INSERT INTO `" . TAB_PREFIX . "content` "
                        . "(`id`, `type`, `code`, `added`, `context`, `sort`, `active`) "
                        . "VALUES (NULL, 'counter', 'last_email_sent', CURRENT_TIMESTAMP, '[]', '0', '1');"
                    );
                } elseif (empty($tempItemWait)) {
                    $this->MyCMS->context['mailStatus'] = 'Wait...';
                } else {
                    // Update & try to send
                    $tempItemB = $this->MyCMS->dbms->query(
                        "UPDATE `" . TAB_PREFIX . "content` SET `added` = CURRENT_TIMESTAMP"
                        . " WHERE `mycmsprojectspecific_content`.`code` = 'last_email_sent';"
                    );
                    Assert::notFalse($tempItemB, 'Update query failed');
                    // Note: sending emails may be turned off in config.php - Debugger::barDump('MAIL SENDING INACTIVE')
                    $tempSend = $this->mail->sendMail(
                        EMAIL_ADMIN,
                        'Test project mail',
                        'Hello, world of mail from PHP version=' . PHP_VERSION
                    );
                    if (is_int($tempSend) & $tempSend > 0) {
                        $this->MyCMS->logger->info('Test mail sent to ' . EMAIL_ADMIN);
                        $this->MyCMS->context['mailStatus'] = 'Sent with result ' . print_r($tempSend, true);
                    } else {
                        $this->MyCMS->logger->warning('Test mail FAILED to ' . EMAIL_ADMIN);
                        $this->MyCMS->context['mailStatus'] = 'Sending failed';
                    }
                }
                return true;
            case 'item-gama':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 3';
                return true;
            case 'item-4':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 4';
                return true;
            case 'product':
                Assert::keyExists($this->get, 'id', 'product MUST be identified by id');
                Assert::scalar($this->get['id']);
                $this->MyCMS->context['product'] = $this->projectSpecific->getProduct((int) $this->get['id']);
                if (is_null($this->MyCMS->context['product'])) {
                    $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
                } else {
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['product']['title'];
                }
                return true;
            case 'search-results': //search _GET[search] contains the search phrase // TODO make search work
                $this->MyCMS->context['limit'] = PAGINATION_LIMIT;
                $this->MyCMS->context['offset'] = isset($this->get['offset']) ? filter_var(
                    $this->get['offset'],
                    FILTER_VALIDATE_INT,
                    ['default' => 0, 'min_range' => 0, 'max_range' => 1e9]
                ) : 0;
                Assert::string($this->get['search']);
                $this->MyCMS->context['results'] = $this->projectSpecific->searchResults(
                    $this->get['search'],
                    (int) $this->MyCMS->context['offset'],
                    $this->MyCMS->context['totalRows']
                );
                //@todo ošetřit empty result
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Výsledky hledání');
                return true;
            default:
                Debugger::log("Undefined template {$this->MyCMS->template}", ILogger::ERROR);
        }
        return false;
    }

    /**
     * For PHP Unit test
     *
     * @return array<array<mixed>>
     */
    public function getVars()
    {
        return [
            'get' => $this->get,
            'session' => $this->session,
        ];
    }
}

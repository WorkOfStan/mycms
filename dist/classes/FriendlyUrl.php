<?php

namespace WorkOfStan\mycmsprojectnamespace;

use Tracy\Debugger;
use WorkOfStan\MyCMS\ArrayStrict;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\MyFriendlyUrl;
use WorkOfStan\mycmsprojectnamespace\ProjectSpecific;

/**
 * Friendly URL set-up
 * (Last MyCMS/dist revision: 2022-07-17, v0.4.7)
 */
class FriendlyUrl extends MyFriendlyUrl
{
    use \Nette\SmartObject;

    /** @var array<mixed> content of array_merge($_GET, $_POST) */
    protected $get;
    /** @var string */
    protected $language = DEFAULT_LANGUAGE; // default is Czech
    /** @var ProjectSpecific */
    private $projectSpecific;
    /** @var string */
    protected $requestUri = ''; // default is homepage
    /** @var string */
    protected $userAgent = '';

    /**
     * @param MyCMS $MyCMS
     * @param array<mixed> $options overides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        // construct a regexp rule from array_keys($MyCMS->TRANSLATIONS) without DEFAULT_LANGUAGE
        $this->parsePathPattern = '~/(' . implode(
            '/|',
            array_diff(array_keys($MyCMS->TRANSLATIONS), [DEFAULT_LANGUAGE])
        ) . '/)?(.*/)?.*?~';
        // TODO consider injecting projectSpecific from Controller instead of creating new instance
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, [
            'language' => $this->language,
            'requestUri' => $this->requestUri,
        ]);
    }
    /**
     * SQL statement to find $token in url_LL column of table(s) with content pieces addressed by FriendlyURL tokens
     * The UNION on tables, where type is stored in a dedicated table of the same name, otherwise a column type within
     * the table is expected is just the simplest way,
     * but SQL statement may be adapted in any way so this method MAY be overidden in this child class
     *
     * @param string $token
     * @return mixed null on empty result, false on database failure or one-dimensional array [id, type] on success
     */
//    protected function findFriendlyUrlToken($token)
//    {
//        Debugger::barDump(
//            ['token' => $token, 'typeToTableMapping' => $this->MyCMS->typeToTableMapping],
//            'findFriendlyUrlToken started'
//        );
//        return $this->MyCMS->fetchSingle('SQL statement to retrieve `id` of `type` that matches the token');
//    }

    /**
     * Returns Friendly Url string for type=id URL if it is available or it returns type=id
     * If the rule is not defined, then info level message is logged by backyard logger if info level is logged
     *
     * @param string $outputKey `type`
     * @param string $outputValue `id`
     * @return string|null
     *     null: do not change the output even if it means returning "?{$outputKey}={$outputValue}"
     *     string: URL - friendly or parametric
     */
    protected function switchParametric($outputKey, $outputValue)
    {
        Debugger::barDump("{$outputKey} => {$outputValue}", 'switchParametric started');
        $this->projectSpecific->language($this->language);
        $get = new ArrayStrict($this->get);
        switch ($outputKey) {
            case 'article':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?article&offset=" . $get->integer('offset') : "?article";
                }
                $content = $this->MyCMS->dbms->fetchStringArray(
                    'SELECT id, name_' . $this->language . ' AS name,'
                    . $this->projectSpecific->getLinkSql("?article&id=", $this->language)
                    . ' FROM `' . TAB_PREFIX . 'content` WHERE active = 1 AND '
                    . (is_numeric($outputValue) ? ' `id` = "' . $this->MyCMS->dbms->escapeSQL(
                        $outputValue
                    ) . '"' : ' code LIKE "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"')
                );
                Debugger::barDump($content, 'content piece');
                return is_null($content) ? self::PAGE_NOT_FOUND : $content['link'];
            case 'category':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?category&offset=" . $get->integer('offset') : "?category";
                }
                $content = $this->MyCMS->dbms->fetchStringArray('SELECT id, name_' . $this->language . ' AS title,'
                    . $this->projectSpecific->getLinkSql("?category=", $this->language)
                    . ' FROM `' . TAB_PREFIX . 'category` WHERE active = 1 '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($content, 'category');
                return is_null($content) ? self::PAGE_NOT_FOUND : (string) $content['link'];
            case 'language': // not necessary, just to make this visible
                return null; // i.e. do not change the output or return "?{$outputKey}={$outputValue}";
            case 'product':
                $content = $this->projectSpecific->getProduct((int) $outputValue);
                Debugger::barDump($content, 'product');
                return is_null($content) ? self::PAGE_NOT_FOUND : (string) $content['link'];
            default:
                $tempInfo = "switchParametric: undefined friendlyfyUrl for {$outputKey} => {$outputValue}";
                $this->MyCMS->logger->info($tempInfo);
                Debugger::barDump($tempInfo, 'switchParametric: undefined');
        }
        return null;
    }
}

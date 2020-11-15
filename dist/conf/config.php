<?php

/**
 * Config for the project
 *
 * EDIT ONLY AS PART OF GIT REPOSITORY
 * FOR LOCAL CHANGES USE config.local.php
 *
 */

ini_set('session.use_strict_mode', '1');
ini_set('display_errors', '0'); // errors only in the log; override it in your config.local.php if you need to

define('DB_HOST', 'localhost');
define('DB_PORT', ini_get('mysqli.default_port'));
define('TAB_PREFIX', 'mycmsprojectspecific_'); // database tables' prefix - use also in phinx.yml as table_prefix field

define('DIR_ASSETS', 'assets/');
define('DIR_TEMPLATE', __DIR__ . '/../template'); // for Latte
define('DIR_TEMPLATE_CACHE', __DIR__ . '/../cache'); // for Latte
define('EXPAND_INFIX', "\t"); // infix for JSON-exapandable values
define('L_UCFIRST', max(MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE) + 1);
define('LOG_FILE', './log/log.txt');
define('MYCMS_SECRET', 'u7-r!!T7.&&7y6ru'); // 16-byte random string, unique per project
define('PATH_MODULE', 10); // length of one node in category.path in digits
define('RECAPTCHA_KEY', '............');
define('URL_RECAPTCHA_VERIFY', 'https://www.google.com/recaptcha/api/siteverify');
//
//for godsdev/backyard
$backyardConf = [
    'logging_level' => 3,
    'error_log_message_type' => 3,
    'logging_file' => __DIR__ . '/../log/backyard-error.log',
    'mail_for_admin_enabled' => false,
];

$debugIpArray = [
    '89.239.1.13', // GODS Ladvi
    '93.99.12.18', // Slavek
    '90.176.60.56', // GODS EOL
];

// configuration for a loaded MyCMS object

$myCmsConf = [
    // Note: update 'language:XX' translations in language-XX.inc.php
    // ISO 639-1 of used languages => name on the language selector
    'TRANSLATIONS' => [
        'en' => 'English',
        'cs' => 'Česky',
        'de' => 'Deutsch',
        'fr' => 'Français',
    ],
    // ISO 639-1 of used languages => label on the language selector
    'LANGUAGE_SELECTOR' => [
        'en' => 'EN',
        'cs' => 'CZ',
        'de' => 'DE',
        'fr' => 'FR',
    ],
    'PAGES_SPECIAL' => [
        'terms-conditions' => 'Terms & Conditions',
        'privacy-policy' => 'Privacy policy',
        'cookies-policy' => 'Recently visited',
        'sitemap' => 'sitemap'
    ],
    'SETTINGS' => [
        'domain' => 'MYCMSPROJECTSPECIFIC.com',
        'form-email' => 'seidl@gods.cz',
        'PATH_HOME' => '0000000001',
//        'PATH_CATEGORY' => '0000000002', // TODO unused in this application
    ],
    'WEBSITE' => [], // this will be filled with $WEBSITE['cs'] or $WEBSITE['en'] according to the current language
    /**
     * RULES for switchParametric are configured in 'templateAssignementParametricRules': map GET parameters to template
     * Handles not only param=value but also param&id=value or param&code=value
     * (Note: code value can't be numeric as it would be treated only as id.)
     * (int) id or (string) code are taken into account only if 'idcode' subfield of templateAssignementParametricRules
     * is equal to `true` - in such case both id and code being empty ends up in 404
     * e.g.
     * 'article' => ['template' => 'article', 'idcode' => true],
     * 'category' => ['template' => 'category', 'idcode' => true],
     * 'line' => ['template' => 'home'], //MyFriendlyURL::TEMPLATE_DEFAULT
     * 'portfolio' => ['template' => 'portfolio'],
     */
    'templateAssignementParametricRules' => [
        'article' => ['template' => 'article', 'idcode' => true], // general articles
        'category' => ['template' => 'category'], // categories of products
        'item-1' => ['template' => 'item-1'], // custom template
        'item-B' => ['template' => 'item-B'], // custom template
        'item-gama' => ['template' => 'item-gama'], // custom template
        'item-4' => ['template' => 'item-4'], // custom template
        'product' => ['template' => 'product', 'idcode' => true], // products
    ],
    // FriendlyUrl::findFriendlyUrlToken maps content types to database tables where they are stored
    'typeToTableMapping' => [
        'article' => 'content',
        'category' => 'category',
        'product' => 'product',
    ]
];
$WEBSITE = [
    'en' => [
        'title' => 'MYCMSPROJECTSPECIFIC Website name',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Website name',
        'slogan' => 'MYCMSPROJECTSPECIFIC Website claim',
        'intro' => 'MYCMSPROJECTSPECIFIC Website description',
        // populates the default English menu:
        //   'activeTabTemplate' => template triggering active tab,
        //   'menuItemLabel' => displayed label,
        //   'relativeUrl' => url after language folder
        'menu' => [
            ['activeTabTemplate' => 'item-1', 'menuItemLabel' => 'Item 1', 'relativeUrl' => 'item-1'],
            ['activeTabTemplate' => 'item-B', 'menuItemLabel' => 'Item 2', 'relativeUrl' => 'item-B'],
            ['activeTabTemplate' => 'item-gama', 'menuItemLabel' => 'Item 3', 'relativeUrl' => 'item-gama'],
            ['activeTabTemplate' => 'item-4', 'menuItemLabel' => 'Item 4', 'relativeUrl' => 'item-4'],
            ['activeTabTemplate' => 'category', 'menuItemLabel' => 'Category 1', 'relativeUrl' => '?category=1'],
        ],
    //TODO populate pageTitle automatically from menu ? maybe within prepareAll use something like
    //context[pageTitle]=isset(website[menu][ref])?website[menu][ref]:context[pageTitle]
    ],
    'cs' => [
        'title' => 'MYCMSPROJECTSPECIFIC Website name',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Website name',
        'slogan' => 'MYCMSPROJECTSPECIFIC Website claim',
        'intro' => 'MYCMSPROJECTSPECIFIC Website description',
        // populates the default Czech menu
        'menu' => [
            ['activeTabTemplate' => 'item-1', 'menuItemLabel' => 'Položka 1', 'relativeUrl' => 'item-1'],
            ['activeTabTemplate' => 'item-B', 'menuItemLabel' => 'Položka 2', 'relativeUrl' => 'item-B'],
            ['activeTabTemplate' => 'item-gama', 'menuItemLabel' => 'Položka 3', 'relativeUrl' => 'item-gama'],
            ['activeTabTemplate' => 'item-4', 'menuItemLabel' => 'Položka 4', 'relativeUrl' => 'item-4'],
            ['activeTabTemplate' => 'category', 'menuItemLabel' => 'Kategorie 1', 'relativeUrl' => '?category=1'],
        ],
    ],
    'de' => [
        'title' => 'MYCMSPROJECTSPECIFIC Webseiten-Name',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Webseiten-Name',
        'slogan' => 'MYCMSPROJECTSPECIFIC Website-Slogan',
        'intro' => 'MYCMSPROJECTSPECIFIC Webseitenbeschreibung',
        // populates the default German menu:
        //   'activeTabTemplate' => template triggering active tab,
        //   'menuItemLabel' => displayed label,
        //   'relativeUrl' => url after language folder
        'menu' => [
            ['activeTabTemplate' => 'item-1', 'menuItemLabel' => 'Artikel 1', 'relativeUrl' => 'item-1'],
            ['activeTabTemplate' => 'item-B', 'menuItemLabel' => 'Artikel 2', 'relativeUrl' => 'item-B'],
            ['activeTabTemplate' => 'item-gama', 'menuItemLabel' => 'Artikel 3', 'relativeUrl' => 'item-gama'],
            ['activeTabTemplate' => 'item-4', 'menuItemLabel' => 'Artikel 4', 'relativeUrl' => 'item-4'],
            ['activeTabTemplate' => 'category', 'menuItemLabel' => 'Kategorie 1', 'relativeUrl' => '?category=1'],
        ],
    ],
    'fr' => [
        'title' => 'MYCMSPROJECTSPECIFIC Nom du site Web',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Nom du site Web',
        'slogan' => 'MYCMSPROJECTSPECIFIC Slogan du site Web',
        'intro' => 'MYCMSPROJECTSPECIFIC Description du site',
        // populates the default French menu
        'menu' => [
            ['activeTabTemplate' => 'item-1', 'menuItemLabel' => 'Article 1', 'relativeUrl' => 'item-1'],
            ['activeTabTemplate' => 'item-B', 'menuItemLabel' => 'Article 2', 'relativeUrl' => 'item-B'],
            ['activeTabTemplate' => 'item-gama', 'menuItemLabel' => 'Article 3', 'relativeUrl' => 'item-gama'],
            ['activeTabTemplate' => 'item-4', 'menuItemLabel' => 'Article 4', 'relativeUrl' => 'item-4'],
            ['activeTabTemplate' => 'category', 'menuItemLabel' => 'Categorie 1', 'relativeUrl' => '?category=1'],
        ],
    ],
];

if (file_exists(__DIR__ . '/config.local.php')) {
    include_once __DIR__ . '/config.local.php'; // use config.local.dist.php as specimen
}
// constants not set in config.local.php
$arrayOfConstants = [
    'DEBUG_VERBOSE' => false,
    'DEFAULT_LANGUAGE' => 'cs',
    'EMAIL_ADMIN' => 'rejthar@gods.cz', // email used by Tracy\Debugger
    'FORCE_301' => true, // enforce 301 redirect to the most friendly URL available
    'FRIENDLY_URL' => false, // default = do not generate friendly URL
    'GA_UID' => 'UA-39642385-1',
    'HOME_TOKEN' => '', // If the web runs in the root of the domain, then the default token `PATHINFO_FILENAME`
    //is an empty string; if the web does not run in the root directory,
    //set its parent folder name (not the whole path) here.
    'NOTIFY_FROM_ADDRESS' => 'notifier-MYCMSPROJECTSPECIFIC@godsapps.eu', // @todo založit příslušnou schránku
    'NOTIFY_FROM_NAME' => 'Notifikátor',
    'PAGE_RESOURCE_VERSION' => 1,
    'PAGINATION_LIMIT' => 10,
//    'PAGINATION_SEARCH' => 10,
//    'PAGINATION_NEWS' => 2,
    'REDIRECTOR_ENABLED' => false, // table redirector with columns old_url, new_url, active exists
    'SMTP_HOST' => 'localhost',
    'SMTP_PORT' => 25,
    'UNDER_CONSTRUCTION' => false,
    'USE_CAPTCHA' => false,
];
foreach ($arrayOfConstants as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}
// If you want to receive fatal errors in mail, set in config.local.php: $backyardConf['mail_for_admin_enabled'] = true;
if (isset($backyardConf['mail_for_admin_enabled']) && $backyardConf['mail_for_admin_enabled']) {
    $backyardConf['mail_for_admin_enabled'] = EMAIL_ADMIN;
}
// default values for feature flags (use keys without spaces to avoid problems in javascript)
$featureFlags = array_merge(
    [
        'console_log_list_values' => false,
        'offline_dev' => false,
    ],
    isset($featureFlags) ? $featureFlags : []
); // use default featureFlags even though nothing is set in `config.local.php`

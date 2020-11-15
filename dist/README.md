# MYCMSPROJECTSPECIFIC
XYZ web
(Folder *dist* is an instant seed of new project.)

## Stack

Linux, Apache (mod_rewrite, mod_header, ssl...)
PHP 7.0
MySQL
PHP libraries
- xml
- mbstring
- mysql

```sh
apt install libapache2-mod-php7.0 apache2 mysql-server git composer php-xml php-mbstring php7.0-mysql
```

Git flow (master,develop,feature,release,fix,hotfix)

## Security

Check that `phinx.yml` and folder `log` are not accessible. Because `mod_alias` not only has to be enabled, but also
in the `/etc/apache2/apache2.conf`, there has to be this setting:
```sh
<Directory /var/www/>
        AllowOverride All # enables .htaccess
        Options FollowSymLinks # not! Options Indexes FollowSymLinks which allows directory browsing
```

## Content

## Web analytics

Gtag version may be used only after <https://github.com/googleanalytics/autotrack> is updated to work with it.
I.e. probably when it will be out of beta.
script/autotrack.V.V.V.js and script/autotrack.V.V.V.js.map are manually taken from current (v2.4.1) <https://github.com/googleanalytics/autotrack> repository.

@todo - as GA events

* Production: UA-XYZ
* Test: UA-39642385-1

## MyCMS dist deployment
* Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder.
* Replace the string `mycmsprojectnamespace` with your project namespace.
* Replace the string `MYCMSPROJECTSPECIFIC` with other website specific information (Brand, Twitter address, phone number, database name, name of icon in manifest.json etc.).
* Default *admin.php* credentials are *john* / *Ew7Ri561*   - MUST be deleted after the real admin account is set up.
* Change `define('MYCMS_SECRET', 'u7-r!!T7.&&7y6ru');` //16-byte random string, unique per project in `conf/config.php`
* Delete this section after the changes above are made

## Deployment

Create database with `Collation=utf8_general_ci`

Create `conf/config.local.php` based on `config.local.dist.php` including the name of the database created above and change any settings you like.

Create `phinx.yml` based on `phinx.dist.yml` including the name of the database created above

Under construction mode may be turned on (for non admin IP adresses i.e. not in `$debugIpArray`) by adding
```php
define('UNDER_CONSTRUCTION', true);
```
to `conf/config.local.php`.

Best practice: Management often uses iPhone or Mac, therefore don't forget to test on Apple devices as well!

### Adding new type of content to be displayed
| Add to this place | Why |
|-----------------------------------------------|------|
| conf/config.php 'templateAssignementParametricRules' | how a GET parameters translate to template |
| conf/config.php 'typeToTableMapping' | type uses specific table for its records |
| Controller::prepareTemplate | Retrieves the content for usage in View layer |
| FriendlyUrl::switchParametric | Checks existence of the content piece and Returns Friendly Url string for type=id URL if it is available or it returns type=id |
| admin.php $AGENDAS | convenient way to administer records within admin.php |
| template/NEW.latte | View layer |

### Ad firewall

If the web will be running behind firewall hence REMOTE_ADDR would contain only firewall IP, then the original REMOTE_ADDR should be passed in another HTTP header, e.g. CLIENT_IP.
So that trusted IPs for debugging may be used.
For this deployment scenarion only (because otherwise it would be a vulnerability) uncomment `isset($_SERVER['HTTP_CLIENT_IP']) ? in_array($_SERVER['HTTP_CLIENT_IP'], $debugIpArray) :` line in `index.php` and `api\*\index.php`.


### `build.sh` runs the following commands
1. `composer update`
2. Note: All changes in database (structure) SHOULD be made by phinx migrations. Create your local `phinx.yml` as a copy of `phinx.dist.yml` to make it work, where you set your database connection into *development* section.
```bash
vendor/bin/phinx migrate -e development # or production or testing
```
3. `vendor/bin/phpunit` to always check the functionality
4. `sass styles/index.sass styles/index.css` to keep order in the generated css

Note: To work on low performing environments, the script accepts number of seconds as parameter to be used as a waiting time between steps.

### reCAPTCHA

Paste this snippet at the end of the <form> where you want the reCAPTCHA widget to appear:
```html
<div class="g-recaptcha" data-sitekey="................"></div>
```

## SEO

Friendly URLs and redirects are *always* processed (if `mod_rewrite` is enabled and Rewrite section in `.htaccess` is present).
If the web runs in the root of the domain, then the default token `PATHINFO_FILENAME` is an empty string;
if the web does not run in the root directory, set its parent folder name (not the whole path) in `conf\config.local.php`:
```php
define('HOME_TOKEN', 'parent-directory');
```

Showing Friendly URLs may be turned off in `conf\config.local.php`:
```php
define('FRIENDLY_URL', false);
```

Constant `FORCE_301` enforces [HTTP 301 redirect](https://en.wikipedia.org/wiki/HTTP_301) to the most friendly URL that is available
(i.e. either friendly URL or parametric URL on application directory) which means
that each page is displayed with a unique URL. It is good for SEO.
Therefore it is not necessary to translate URL within content (e.g. from the parametric to friendly) as they end up on the right unique URL, anyway.

Given that
`/?product&id=1` has friendly URL `/alfa` and `/?product&id=2` has friendly URL `/beta`, then:

| | FRIENDLY_URL = false                          |  FRIENDLY_URL = true |
|-|-----------------------------------------------|------|
| **FORCE_301 = false** | | |
| | `/?product&id=1` displays *`product 1`*            | `/?product&id=1` displays *`product 1`*          |
| |  `/?product&id=1&x=y` displays *`product 1`*       | `/?product&id=1&x=y` displays *`product 1`*      |
| |  `/alfa` displays *`product 1`*                    |  `/alfa` displays *`product 1`*                  |
| |  `/alfa?product&id=2` displays *`product 2`*       |  `/alfa?product&id=2` displays *`product 2`*     |
| |  ProjectCommon->getLinkSql() generates link to `/?product&id=1` |  **ProjectCommon->getLinkSql() generates link to `/alfa`**  |
| **FORCE_301 = true**  | | |
| |  `/?product&id=1` displays *`product 1`*             | **`/?product&id=1` redirects to `/alfa`**      |
| |  `/?product&id=1&x=y` displays *`product 1`*         | **`/?product&id=1&x=y` redirects to `/alfa`**  |
| |  `/alfa` displays `product 1`                        |  `/alfa` displays *`product 1`*                |
| |  **`/alfa?product&id=2` redirects to `/?product&id=2`** |  **`/alfa?product&id=2` redirects to `/beta`** |
| |  ProjectCommon->getLinkSql() generates link to `/?product&id=1` |  ProjectCommon->getLinkSql() generates link to `/alfa`  |

Inner workings of friendly URL mechanism are described in [MyCMS/README.md](https://github.com/GodsDev/mycms#how-does-friendly-url-works-within-controller)

TODO: make more clear
* Tabulky `#_content`, `#_product` musí mít sloupce `url_##` (## = dvoumístný kód pro všechny jazykové verze).
* Do `url_##` se uloží "webalizované" názvy dané stránky/produktu (dle funkce `Tools::webalize`). Výjimkou může být `_content`, který není plnohodnotná stránka – ten může obsahovat `NULL`. Převod lze zprvu udělat programaticky (je to na pár řádků), pak do CMS přidat tlačítko pro převod nebo převod udělat při uložení.

TODO: ?article=1 vs ?article&id=1 a souvislost s 'idcode' => true ?

### Example of rules
* `/?product=4` → `/konzultacni-poradenctvi`
* `/?page=about` → `/o-firme-sro`
* `/?news=37` → `/news/albus-novak-is-the-new-commercial-director-at-firma-sro`

TODO: explain and translate:
Jazyk je uveden jako první a to dvoumístným kódem a lomítkem, např. `/cs/logistika`. Defaultní jazyk (čeština) takto uveden být nemá.

TODO: explain and translate:
Interně se jazyk do políčka `url_##` pro jiné (nedefaultní) jazyky nevkládá

### Language management

Languages are identified by two letter combination according to [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes).

#### Used languages
Language versions (or translations) are specified when instatiating the MyCMS object in [conf/config.php](conf/config.php). For example:
```php
[
    ...
    'TRANSLATIONS' => [
        'en' => 'ENG',
        'zh' => '中文',
        'cs' => 'CZ',
    ],
]
```
For each language a corresponding file `language-xx.inc.php` is expected.

[.htaccess](.htaccess) is ready for languages `de|en|fr|sk|zh` to show content in the appropriate language folder
(`cs` is considered as the default language, so it is accessible directly in application root),
where page resouces may be in folders `styles|assets|fonts|images|scripts` which ignore the language directory.

#### Default language
Default language set in [conf/config.php](conf/config.php) as constant `'DEFAULT_LANGUAGE' => 'cs',`
is the language in which the web starts without any additional information about language
(such as language folder or session).
The default language is typically shown in the application root.

#### Accepted types of URL

* /alfa .. named page in the DEFAULT_LANGUAGE
* /en/alfa .. named page in another language
* /?product&id=3 .. parametric page in the DEFAULT_LANGUAGE
* /?category=1 .. parametric page in the DEFAULT_LANGUAGE
* /en/?product&id=3 .. parametric page in another language
* /en/?category=1 .. parametric page in another language
* /?language=de .. language switch
* /de/ .. default page in German
* /?product&id=3&language=de .. parametric page in another language

## CMS notes

### Agenda
Agenda is an item in the `admin.php` left menu that refers to a set of rows in database. (All tables can be also accessed from the bottom of the page.)

Examples of settings:
```php
$tmp = $language;
$AGENDAS = array(
    'division' => array('column' => 'division_' . $tmp),
    'page' => array('table' => 'content', 'where' => 'type="page"', 'column' => "\0CONCAT(code,'|',page_$tmp)"),
    'news' => array('table' => 'content', 'where' => 'type="news"', 'column' => 'content_' . $tmp, 'prefill' => array('type' => 'news')),
    'slide' => array('table' => 'content', 'where' => 'type="slide"', 'column' => 'content_' . $tmp, 'prefill' => array('type' => 'event')),
    'event' => array('table' => 'content', 'where' => 'type="event"', 'column' => "\0CONCAT(page_$tmp,'|',content_$tmp)", 'prefill' => array('type' => 'event')),
);

$AGENDAS = array(
    'category' => array('path' => 'path'),
    'press' => array('table' => 'content', 'where' => 'type="press"', 'prefill' => array('type' => 'press')),
    'testimonial' => array('table' => 'content', 'where' => 'type="testimonial"', 'column' => 'description_' . DEFAULT_LANGUAGE, 'prefill' => array('type' => 'testimonial')),
    'claim' => array('table' => 'content', 'where' => 'type="claim"', 'column' => 'description_' . DEFAULT_LANGUAGE, 'prefill' => array('type' => 'claim')),
    'perex' => array('table' => 'content', 'where' => 'type="perex"', 'column' => 'description_' . DEFAULT_LANGUAGE, 'prefill' => array('type' => 'perex'))
);

$AGENDAS = [
    'item' => [
        'column' => 'name',     // Name of column where the value is displayed from
        'where' => 'active="1"' // Filter on displayed columns
    ],
    'consumption' => [
        'column' => 'created',
        'where' => 'active="1"'
    ],
    'amount' => [
        'column' => 'created',
        'where' => 'active="1"'
    ],
    'page' => [
        'table' => 'content',
        'where' => 'type="page"',
        'column' => ['code', "name_{$_SESSION['language']}"], // use array of columns to add their value concatentated in the list below agenda (note: \0CONCAT was used for this before proper SQL escaping)
        'prefill' => [
            'type' => 'page',
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',   // results to date('Y-m-d\TH:i:s')
        ],
    ],
];
```
if path used: 'CONCAT(REPEAT("… ",LENGTH(' . $this->MyCMS->dbms->escapeDbIdentifier($options['path']) . ') / ' . PATH_MODULE . ' - 1),' . $options['table'] . '_' . DEFAULT_LANGUAGE . ')'

(TODO: explain better with examples.)

### Asset folder structure
* `assets/career/` - pro média spojené s pracovními příležitostmi
* `assets/news/` - pro obrázky novinek
* `assets/products/` - pro obrázky produktů
* `assets/product-sheet-cs/` - pro CS verze PDF produktů
* `assets/product-sheet-en/` - pro EN verze PDF produktů
* `assets/section-bg/` - background of some product sections
* `assets/testimonials/` - logos of companies with testimonial
* `assets/videos/` - videos
* `assets/slides/` - pro slidy
* `assets/references/` - logos of companies with reference
* `images` - other miscelaneous images (logos, page headers, etc.)

Note: assets expects only ONE sub-level.

#### admin.php expects
* [Summernote](https://summernote.org/getting-started/#installation) v.0.8.18 (2020-05-20) (styles/summernote.css, styles/font/summernote.*, scripts/summernote.js, scripts/summernote.js.map)
* scripts\bootstrap.js
* scripts\admin-specific.js
* scripts\ie10-viewport-bug-workaround.js
* styles\bootstrap.css
* styles\bootstrap-datetimepicker.css
* styles\font-awesome.css
* styles\ie10-viewport-bug-workaround.css
* fonts\fa*.*

### Admin UI
Add protected functions to Admin.php according to MyAdmin.php in order to add menu relevant for the application, such as Translations, FriendlyURL, Divisions and products, etc.

### Redirector

Note: Both the `old_url` and `new_url` MUST start with `/`.

## Debugging

```php
// to write out a variable to `Tracy`
// array $options of Debugger::barDump (Dumper::DEPTH, Dumper::TRUNCATE, Dumper::LOCATION, Dumper::LAZY)
\Tracy\Debugger::barDump($mixedVar, 'Info why to show it', $options = []);

// to log a variable value into its error level log
\Tracy\Debugger::log($stringVar, \Tracy\ILogger::DEBUG); // Note: \Tracy\ILogger::DEBUG equals 'debug'

// to throw an exception
throw new \Exception('Exception description');
```

`$debugIpArray` in `config.php` contains IPs for which Tracy will be displayed.

Recommendation: use `webmozart/assert` (instead of `beberlei/assert`) as it is already required by `phpdocumentor/reflection-docblock` required by `phpspec/prophecy` required by `phpunit/phpunit`.
Note phpunit is only require-dev, so `webmozart/assert` MUST be required in the `composer.json` of this application.

### REST API

Note: `header("Content-type: application/json");` in outputJSON hides Tracy

## Coding style and linting

super-linter uses PHPSTAN to identify PHPDoc errors
* but github-action doesn't have access to libraries declared in composer (ignoreErrors)
* and it needs to know which global constants are used (`.github/linters/conf/constants.php`) on top of standard config files
* and where to look for present classes (scanDirectories), hence following files:
* `.github/linters/phpstan.neon` - for super-linter (of this app) on github
* `phpstan.neon.dist` - for local PHPSTAN
* `conf/phpstan.common.neon` - both for local and super-linter on github PHPSTAN
* `conf/phpstan.mycms.neon` - both for local and super-linter (of this app or mycms) on github PHPSTAN
* Note: if your DEFAULT_BRANCH is not `develop`, change `.github/linters/phpstan.neon` accordingly
* Note: when code becomes stable, change VALIDATE_ALL_CODEBASE to `false`

* TODO: .eslintrc.yml and dist/.eslintrc.yml - keep or delete?

## Error handling

If your IP is among `$debugIpArray` you will see an Exception on screen. Otherwise, you will get "nice" Tracy 500 Internal server error.

Logs are in folder `log`:
* `exception.log` contains fatal errors by Tracy\Debugger
* `error.log` contains recoverable erros by Tracy\Debugger
* `debug.log` contains debug info by Tracy\Debugger
* `backyard-error.log.YYYY-MM.log` by PSR-3 logger implemented in GodsDev\Backyard\BackyardError
* `sqlYYYY-MM-DD.sql` contains content changes by CMS as SQL statements with timestamp

## Templating

[`latte` templates](https://latte.nette.org/) are used.

## Visual style

Pages have view-TEMPLATE class in <body/> to allow for exceptions.

Convert SASS to CSS (performed also by [build.sh](build.sh)) by
```sh
sass styles/index.sass styles/index.css
```

When changing index.css, index.js or admin.js, update `PAGE_RESOURCE_VERSION` in `config.php` in order to force cache reload these resources.

## TODO

### TODO lokalizace
* 200526: jazykový přepínač rovnou vybere správné URL, pokud pro daný jazky existuje
* 200608: describe scenario when no language is `default` in terms that all pages run within /iso-639-1/ folder

### TODO CMS
* 200610: bool field show as on/off 1/0 true/false or something else more reasonable than int input box
* 200712: when adding new Content automatically uncheck the NULL checkbox so that something is saved!
* 200828: content_XX HTML field (for article) doesn't automatically uncheck null checkbox, therefore the UX is that you fill-in new text and it is not saved

### TODO SEO


### TODO vizualizace


### TODO security


### TODO other
* 190611: add article and search page types including controller tests
* 190611: Make SASS to CSS conversion automatic (e.g. gulp or Github Action?)
* 200712: migrate popper <https://popper.js.org/docs/v2/migration-guide/> incl. map --> admin.php expects section
* 200712: update bootstrap <https://getbootstrap.com/> incl. map --> admin.php expects section
* 200712: update jquery <https://jquery.com/> incl. map --> admin.php expects section
* 200712: update fontawesome --> admin.php expects section
* 200712: remove unnecessary `sql =` constructs
* 200802: test with 2 categories
* 200802: image for product and category in assets
* 200826: <http://localhost:8080/godsdev/mycms/dist/admin.php?language=fr> nastaví jazyk položek do fr, ale v Settings se to nedá: ať se dá
* 200921: (MyCMS) properly fix message: '#Parameter #2 $newvalue of function ini_set expects string, true given.#'    path: /github/workspace/set-environment.php

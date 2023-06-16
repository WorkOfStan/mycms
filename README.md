# MyCMS
[![Total Downloads](https://img.shields.io/packagist/dt/workofstan/mycms.svg)](https://packagist.org/packages/workofstan/mycms)
[![Latest Stable Version](https://img.shields.io/packagist/v/workofstan/mycms.svg)](https://packagist.org/packages/workofstan/mycms)
[![Lint Code Base](https://github.com/WorkOfStan/mycms/actions/workflows/linter.yml/badge.svg)](https://github.com/WorkOfStan/mycms/actions/workflows/linter.yml)
[![PHP Composer + PHPUnit + PHPStan](https://github.com/WorkOfStan/mycms/actions/workflows/php-composer-phpunit.yml/badge.svg)](https://github.com/WorkOfStan/mycms/actions/workflows/php-composer-phpunit.yml)

Brief MVC framework for interactive sites including general administration.
This framework allows you to create an app just by simple configuration and keeping the framework up-to-date by composer while letting you use the vanilla PHP as much as possible.
It works as a devstack which you install and then write your classes specific for your project.
The boilerplate project is prepared in `dist` folder to be adapted as needed and it uses this `WorkOfStan\MyCMS` library out-of-the-box.

MyCMS is designed to be used with following technologies:
- [jQuery](https://jquery.org/) and [Bootstrap (version 4)](https://getbootstrap.com/docs/4.0/components/): for presentation
- [Latte](http://latte.nette.org/): for templating
- [MySQL](https://dev.mysql.com/)/[MariaDB](http://mariadb.com): for database backend
- [Tracy](https://github.com/nette/tracy): for debugging
- [Nette\SmartObject](https://doc.nette.org/en/3.0/smartobject): for ensuring strict PHP rules
- [Psr\Log\LoggerInterface](https://www.php-fig.org/psr/psr-3/): for logging
- [WorkOfStan\Backyard\BackyardMysqli](https://github.com/WorkOfStan/backyard/blob/main/classes/BackyardMysqli.php): for wrapping SQL layer

## Installation
Apache modules `mod_alias` (for hiding non-public files) and `mod_rewrite` (for friendly URL features) are expected.

Once [composer](https://getcomposer.org/) is installed, execute the following command in your project root to install this library:
```sh
composer require workofstan/mycms:^0.4.9
```
Most of library's classes use prefix `My`.
To develop your project, create your own classes as children inheriting MyCMS' classes in the `./classes/` directory and name them without the initial `My` in its name.  

```php
$MyCMS = new \WorkOfStan\MyCMS\MyCMS(
    [
        // compulsory
        'logger' => $logger, // object \Psr\Log\LoggerInterface
        //optional
    ]
);

//Finish with Latte initialization & Mark-up output
$MyCMS->renderLatte(DIR_TEMPLATE_CACHE, "\\vendor\\ProjectName\\Latte\\CustomFilters::common", $params);
```

Files `process.php` and `admin-process.php` MUST exist as they process forms.

Note: `$MyCMS` name is expected by `ProjectSpecific extends ProjectCommon` class (@todo replace global $MyCMS by parameter handling)

## Deployment

### `/dist`
Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder in order to start easily.
Replace the string `MYCMSPROJECTNAMESPACE` with your project namespace. (TODO: rector...)
Replace the string `MYCMSPROJECTSPECIFIC` with other site specific information (Brand, Twitter address, phone number, database table_prefix in phinx.yml...).
If you want to use your own table name prefix, please change the database related strings before first running [`./build.sh`](dist/build.sh).

To adapt the content and its structure either adapt migrations [content_table](dist/db/migrations/20200607204634_content_table.php) and [content_example](dist/db/migrations/20200703213436_content_example.php)
before first running build
or adapt the database content after running build
or run build, see for yourself how it works, then adapt migrations, drop tables and run build again.

The table with users and hashed passwords is named `TAB_PREFIX . 'admin'`.

It is recommanded to adapt classes Contoller.php, FriendlyUrl.php and ProjectSpecific.php to your needs following the recommendations in comments.
For deployment look also to [Deployment chapter](dist/README.md#deployment) and [Language management](dist/README.md#language-management) in dist/README.md.

MyCMS is used only as a library, so the project using it SHOULD implement `RedirectMatch 404 vendor\/` statement as prepared in `dist/.htaccess` to keep the library hidden from web access.

## Admin UI
Admin UI is displayed by MyAdmin::outputAdmin in this structure:
|Navigation|Search|
|--|--|
|Agendas|Main|

Element overview:
|Navigation = SpecialMenuLinks + Media+User+Settings|Search|
|--|--|
|Agendas (as in $AGENDAS in admin.php)|Messages<br>Workspace: table/row/media/user/project-specific<br>Dashboard: List of tables|

### Navigation
- special Admin::outputSpecialMenuLinks
- default: Media+User+Settings MyAdmin::outputNavigation

### Search
- Admin class variable `$searchColumns` defines an array in format database_table => [`id`, list of fields to be searched in], e.g.
```php
    protected $searchColumns = [
        'product' => ['id', 'name_#', 'content_#'], // "#" will be replaced by current language
    ];
```

### Agendas
- MyAdmin::outputAgendas
- defined in $AGENDAS in admin.php

### Main
- Messages
- Workspace: one of the following
  - $_GET['search'] => MyAdmin::outputSearchResults
  - $_GET['table'] => MyAdmin::outputTable
    -- $_GET['where'] is array => Admin::outputTableBeforeEdit . MyAdmin::tableAdmin->outputForm . Admin::outputTableAfterEdit
    -- $_POST['edit-selected'] => MyAdmin::outputTableEditSelected(false)
    -- $_POST['clone-selected'] => MyAdmin::outputTableEditSelected(true)
    -- else => Admin::outputTableBeforeListing . MyAdmin::tableAdmin->view . Admin::outputTableAfterListing
  - $_GET['media'] => MyAdmin::outputMedia media upload etc.
  - $_GET['user'] => MyAdmin::outputUser user operations (logout, change password, create user, delete user)
  - Admin::projectSpecificSectionsCondition => Admin::projectSpecificSection project-specific admin sections
- Dashboard: List of tables MyAdmin::outputDashboard

## Admin notes

### Database

Columns of tables displayed in admin can use various features set in the comment:
| comment | feature                               |
|---------|---------------------------------------|
| `{"display":"html"}` | HTML editor Summernote |
| {"display":"layout-row"} | ?? |
| {"display":"option"} | Existing values are offered in select box |
| {"display":"option","display-own":1} | ... and an input box for adding previously unused values |
| {"display":"path"} | ?? |
| {"display":"texyla"} | ?? Texyla editor |
| {"edit": "input"} | zatím nic: todo: natáhnout string z prvního pole na stránce a webalize |
| {"edit":"json"} | rozpadne interní json do příslušných polí --- ovšem pokud prázdné, je potřeba vložit JSON (proto je default '{}') |
| {"foreign-table":"category","foreign-column":"category_en"} | odkaz do jiné tabulky ke snadnému výběru |
| {"foreign-table":"category","foreign-column":"category_en","foreign-path":"path"} | ?? |
| {"required":true} | ?? |

TODO: active=0/1 display as on/off button

TODO: better explain.

### clientSideResources
In `class/Admin.php` you can redefine the `clientSideResources` variable with resources to load to the admin. Its default is:
```php
    protected $clientSideResources = [
        'js' => [
            'scripts/jquery.js',
            'scripts/popper.js',
            'scripts/bootstrap.js',
            'scripts/admin.js?v=' . PAGE_RESOURCE_VERSION,
        ],
        'css-pre-admin' => [
            'styles/bootstrap.css',
        ],
        'css' => [
            'styles/font-awesome.css',
            'styles/ie10-viewport-bug-workaround.css',
            'styles/bootstrap-datetimepicker.css',
            'styles/summernote.css',
            'styles/admin.css?v=' . PAGE_RESOURCE_VERSION,
        ]
    ];
```

`admin.css` may be inherited to a child project, however as vendor folder SHOULD have denied access from browser,
the content of that standard `admin.css` MUST be available through method `MyAdmin::getAdminCss`.

## Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

Note that `dist` folder contains the starting MyCMS based project deployment and testing runs through `dist` as well,
so for development, the environment has to be set up for `dist` as well.

Note: running `vendor/bin/phpunit` from root will result in using MyCMS classes from the root Classes even from `mycms/dist/Test`.
While running `vendor/bin/phpunit` from `dist` will result in using MyCMS classes from the `dist/vendor/workofstan/mycms/classes`.

GitHub actions' version of PHPUnit uses config file [phpunit-github-actions.xml](phpunit-github-actions.xml) that ignores `Distribution Test Suite`
because MySQLi environment isn't prepared (yet) and HTTP requests to self can't work in CLI only environment.

### Reusing workflows
As dist/.github/workflows [reuses](https://docs.github.com/en/actions/using-workflows/reusing-workflows) some .github/workflows through workflow_call,
it is imperative not to introduce ANY BREAKING CHANGES there.
The reused workflow may be referenced by a branch, tag or commit and doesn't support [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
```sh
    # Working examples
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@main # ok, but all encompassing
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@v0.4.9 # it works

    # Failing examples
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@v0.4
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@^v0.4
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@^0.4
    uses: WorkOfStan/MyCMS/.github/workflows/phpcbf.yml@v0    
```
Therefore, if a breaking change MUST be introduce, create another workflow to be reused instead of changing the existing one!

### PHPStan

Till PHP<7.1 is supported, neither `phpstan/phpstan-webmozart-assert` nor `rector/rector` can't be required-dev in composer.json.
Therefore, to properly take into account Assert statements by PHPStan (relevant for level>6), do a temporary (i.e. without commiting it to repository)
```sh
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress
composer require --dev rector/rector --prefer-dist --no-progress
```
and use [conf/phpstan.webmozart-assert.neon](conf/phpstan.webmozart-assert.neon) to allow for `phpstan --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M`.

Prepared scripts
[./phpstan.sh](phpstan.sh)
and
[./phpstan-remove.sh](phpstan-remove.sh)
can be used to start (or remove) the static analysis.
(TODO: call the dist scripts from root to DRY.)

## How does Friendly URL works within Controller

[SEO settings details including language management in `dist` folder](dist/README.md#seo)

```php
new Controller(['requestUri' => $_SERVER['REQUEST_URI']])
│   // request URI is set in multiple places
│   ->requestUri
│   ->projectSpecific->requestUri
│   ->friendlyUrl->requestUri
│   ->friendlyUrl->projectSpecific->requestUri
│   ->result['template'] = TEMPLATE_DEFAULT
│
└───run()
│   └── $controller->MyCMS->template = $this->result['template'];
│   │
│   └───$controller->friendlyUrl
│       └── ->determineTemplate(['REQUEST_URI' => $this->requestUri]) // returns mixed string with name of the template when template determined, array with redir field when redirect, bool when default template SHOULD be used
│            ├── ->friendlyIdentifyRedirect(['REQUEST_URI' => $this->requestUri]) // returns mixed 1) bool (true) or 2) array with redir string field or 3) array with token string field and matches array field (see above)
│            │   ├──if $token === self::PAGE_NOT_FOUND
│            │   │    └──$this->MyCMS->template = self::TEMPLATE_NOT_FOUND
│             <──────── @return true
│                 ├──FORCE_301
│                 │    ├── ->friendlyfyUrl(URL query) // returns string query key of parse_url, e.g  var1=12&var2=b
│                 │    │   └── ->switchParametric(`type`, `value`) // project specific request to database returns mixed null (do not change the output) or string (URL - friendly or parametric)
│                 │    │        └──If something new calculated, then
│             <────────────── @return redirWrapper(URL - friendly or parametric)
│                 │    └── if !isset($matches[1]) && ($this->language != DEFAULT_LANGUAGE) // no language subpatern and the language isn't default
│             <─────────── @return 302 redirWrapper(languageFolder . interestingPath) // interestingPath is part of PATH beyond applicationDir
│                 ├──REDIRECTOR_ENABLED
│                 │    └──if old_url == interestingPath (=part of PATH beyond applicationDir)
│             <─────────── @return redirWrapper(new_path)
│                 └──If there are more (non language) folders, the base of relative URLs would be incorrect, therefore
│             <──────── @return **redirect** either to a base URL with query parameters or to a 404 Page not found
│             <──── @return [token, matches]
│         <──── @return array with redir field when redirect || bool when default template SHOULD be used
│            │
│            ├──[token, matches]
│            ├──loop through $myCmsConf['templateAssignementParametricRules'] and if $this->get[`type`] found:
│         <────── @return template || `TEMPLATE_NOT_FOUND` (if invalid `value`)
│            │
│            └── ->pureFriendlyUrl(['REQUEST_URI' => $this->requestUri], $token, $matches); //FRIENDLY URL & Redirect calculation where $token, $matches are expected from above
│                       ├──default scripts and language directories all result into the default template
│             <─────────── @return self::TEMPLATE_DEFAULT
│         <──── @return self::TEMPLATE_DEFAULT
│                       │
│                       └── ->findFriendlyUrlToken(token) // project specific request to database @return mixed null on empty result, false on database failure or one-dimensional array [id, type] on success
│                            │                              If there is a pure friendly URL, i.e. the token exactly matches a record in content database, decode it internally to type=id
│                            │                              SQL statement searching for $token in url_LL column of table(s) with content pieces addressed by FriendlyURL tokens
│                            │                              Overide the method if the default UNION on tables, where relevant types are stored, isn't sufficient
│                            │   spoof $this->get[$found['type']] = $this->get['id'] = $found['id']
│             <────────────── @return $this->determineTemplate(['REQUEST_URI' => $this->requestUri]) RECURSION
│             <─────────── @return null
│         <──── null => @return self::TEMPLATE_NOT_FOUND
│   <──── redir or continue with calculated $controller->MyCMS->template
```

## TODO

### TODO Administration
* 200314: administrace FriendlyURL je v F/classes/Admin::outputSpecialMenuLinks() a ::sectionUrls() .. zobecnit do MyCMS a zapnout pokud FRIENDLY_URL == true
* 200314 v Admin.php mít příslušnou editační sekci FriendlyURL (dle F project) .. pokud lze opravdu zobecnit
* 200526: CMS: If Texy is used (see only in MyTableAdmin `($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')` then describe it. Otherwise remove it from composer.json, Latte\CustomFilters\, ProjectCommon, dist\index.php.

### TODO Governance
* 190705: v classes\LogMysqli.php probíhá logování `'log/sql' . date("Y-m-d") . '.log.sql');` do aktuálního adresáře volajícího skriptu - což u API není výhodné. Jak vycházet z APP_ROOT?
* 200526: describe jQuery dependencies; and also other js libraries (maybe only in dist??)
* 200529: Minimum of PHP 7.2 required now: PHPUnit latest + Phinx latest <https://github.com/cakephp/phinx/releases> .. planned for release 0.5.0
* 200608: replace all `array(` by `[`
* 200819: refactor FORCE_301, FRIENDLY_URL and REDIRECTOR_ENABLED to a variable, so that all scenarios can be PHPUnit tested
* 200819: consider REQUEST_URI query vs \_GET - shouldn't just one source of truth be used?
* 200921: for PHP/7.1.0+ version use protected for const in MyCommon, MyFriendlyUrl, MyAdminProcess.php

### TODO UI
* 220716 Admin Translations and `Urls` module should have Tabs displayed by the Core (not the App)
* 230309 'Pravidla pro užívání portálu': 'Terms & conditions', 'Pravidla pro užívání portálu': 'Terms & Bedingungen' shouldn't show as &amp; - either noescape filter in inc-footer.latte or change `L10n::translate return Tools::h($text);`

### TODO SECURITY
* 190723: pokud jsou v té samé doméně dvě různé instance MyCMS, tak přihlášením do jednoho admin.php jsem přihlášen do všech, i když ten uživatel tam ani neexistuje
* 220513, Latte::2.11.3 Notice: Engine::addFilter(null, ...) is deprecated, use addFilterLoader() since ^2.10.8 which requires php: >=7.1 <8.2 (stop limiting "latte/latte": ">=2.4.6 <2.11.3")

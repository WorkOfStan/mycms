# MyCMS
[![Total Downloads](https://img.shields.io/packagist/dt/godsdev/mycms.svg)](https://packagist.org/packages/godsdev/mycms)
[![Latest Stable Version](https://img.shields.io/packagist/v/godsdev/mycms.svg)](https://packagist.org/packages/godsdev/mycms)
[![Lint Code Base](https://github.com/GodsDev/mycms/workflows/Lint%20Code%20Base/badge.svg)](https://github.com/GodsDev/mycms/actions?query=workflow%3A%22Lint+Code+Base%22)
[![PHP Composer + PHPUnit](https://github.com/GodsDev/mycms/workflows/PHP%20Composer%20+%20PHPUnit/badge.svg)](https://github.com/GodsDev/mycms/actions?query=workflow%3A%22PHP+Composer+%2B+PHPUnit%22)

Brief MVC framework for interactive websites including general administration.
Works as a devstack which you install and then write your classes specific for the project.
The boilerplate project is prepared in `dist` folder to be adapted as needed and it uses this `GodsDev\MyCMS` library out-of-the-box.

MyCMS is designed to be used with following technologies:
- [jQuery](https://jquery.org/) and [Bootstrap (version 4)](https://getbootstrap.com/docs/4.0/components/): for presentation
- [Latte](http://latte.nette.org/): for templating
- [MySQL](https://dev.mysql.com/)/[MariaDB](http://mariadb.com): for database backend
- [Tracy](https://github.com/nette/tracy): for debugging
- [Nette\SmartObject](https://doc.nette.org/en/3.0/smartobject): for ensuring strict PHP rules
- [Psr\Log\LoggerInterface](https://www.php-fig.org/psr/psr-3/): for logging
- [GodsDev\Backyard\BackyardMysqli](https://github.com/GodsDev/backyard/blob/master/GodsDev/Backyard/BackyardMysqli.php): for wrapping SQL layer

## Installation
Apache modules `mod_alias` (for hiding non-public files) and `mod_rewrite` (for friendly URL features) are expected.

Require MyCMS in [`composer.json`](https://getcomposer.org/).
```json
{
    ...
    "required": {
        "GodsDev/mycms": "^0.3.15" //or the latest version
        ...
    }
}
```
The `composer install` command will load the library's files into `./vendor/godsdev/mycms/`. The library's classes are in `./vendor/godsdev/mycms/classes/`
and most of them use prefix `My`.

To customize the project, create your own classes as children inheriting MyCMS' classes in the `./classes/` directory and name them without the initial `My` in its name.  

```php
$MyCMS = new \GodsDev\MyCMS\MyCMS(
    [
        // compulsory
        'logger' => $logger, // object \Psr\Log\LoggerInterface
        //optional
    ]
);

//Finish with Latte initialization & Mark-up output
$MyCMS->renderLatte(DIR_TEMPLATE_CACHE, "\\GodsDev\\ProjectName\\Latte\\CustomFilters::common", $params);
```

Files `process.php` and `admin-process.php` MUST exist and process forms.

Note: `$MyCMS` name is expected by `ProjectSpecific extends ProjectCommon` class (@todo replace global $MyCMS by parameter handling)

## Deployment

### `/dist`
Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder in order to easily start.
Replace the string `MYCMSPROJECTNAMESPACE` with your project namespace.
Replace the string `MYCMSPROJECTSPECIFIC` with other website specific information (Brand, Twitter address, phone number, database table_prefix in phinx.yml...).
If you want to use your own table name prefix, it is recommanded to change database related strings before first running [`./build.sh`](dist/build.sh).

To adapt the content and its structure either adapt migrations [content_table](dist/db/migrations/20200607204634_content_table.php) and [content_example](dist/db/migrations/20200703213436_content_example.php)
before first running build
or adapt the database content after running build
or run build, see for yourself how it works, then adapt migrations, drop tables and run build again.

The table with users and hashed passwords is named `TAB_PREFIX . 'admin'`.

It is recommanded to adapt classes Contoller.php, FriendlyUrl.php and ProjectSpecific.php to your needs following the recommendations in comments.
For deployment look also to [Deployment chapter](dist/README.md#deployment) and [Language management](dist/README.md#language-management) in dist/README.md.

MyCMS is used only as a library, so the application using it SHOULD implement `RedirectMatch 404 vendor\/` statement as prepared in `dist/.htaccess` to keep the library hidden from web access.

## Admin notes

### Database

Columns of tables displayed in admin can use various features set in the comment:
| comment | feature                               |
|---------|---------------------------------------|
| {"display":"html"} | HTML editor Summernote |
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
the content of that standard `admin.css` MUST be available through method MyAdmin::getAdminCss.

## Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

Note that `dist` folder contains the starting MyCMS based project deployment and testing runs through `dist` as well,
so for development, the environment has to be set up for `dist` as well.

Note: running `vendor/bin/phpunit` from root will result in using MyCMS classes from the root Classes even from `mycms/dist/Test`.
While running `vendor/bin/phpunit` from `dist` will result in using MyCMS classes from the `dist/vendor/godsdev/mycms/classes`.

GitHub actions' version of PHPUnit uses config file [phpunit-github-actions.xml](phpunit-github-actions.xml) that ignores `Distribution Test Suite`
because MySQLi environment isn't prepared (yet) and HTTP requests to self can't work in CLI only environment.

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

## TROUBLESHOOTING

| Home page returns 404 Not found | `define('HOME_TOKEN', 'parent-directory');` in `config.local.php` |

## TODO

### TODO Administration
* 200314: administrace FriendlyURL je v F4T/classes/Admin::outputSpecialMenuLinks() a ::sectionUrls() .. zobecnit do MyCMS a zapnout pokud FRIENDLY_URL == true
* 200314 v Admin.php mít příslušnou editační sekci FriendlyURL (dle F4T) .. pokud lze opravdu zobecnit
* 200526: CMS: If Texy is used (see only in MyTableAdmin `($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')` then describe it. Otherwise remove it from composer.json, Latte\CustomFilters\, ProjectCommon, dist\index.php.

### TODO Governance
* 190705: v classes\LogMysqli.php probíhá logování `'log/sql' . date("Y-m-d") . '.log.sql');` do aktuálního adresáře volajícího skriptu - což u API není výhodné. Jak vycházet z APP_ROOT?
* 200526: update jquery 3.2.1 -> 3.5.1 and describe dependencies; and also other js libraries (maybe only in dist??)
* 200529: Minimum of PHP 7.2 required now: PHPUnit latest + Phinx latest <https://github.com/cakephp/phinx/releases> .. planned for release 0.5.0
* 200608: replace all `array(` by `[`
* 200819: refactor FORCE_301, FRIENDLY_URL and REDIRECTOR_ENABLED to a variable, so that all scenarios can be PHPUnit tested
* 200819: consider REQUEST_URI query vs \_GET - shouldn't just one source of truth be used?
* 181228 <https://symfony.com/doc/current/components/yaml.html> -- pro načítání db spojení rovnou z yml namísto duplicitního zadávání do private.conf.php
* 200921: for PHP/7.1.0+ version use protected for const in MyCommon, MyFriendlyUrl, MyAdminProcess.php

### TODO SECURITY
* 190723: pokud jsou v té samé doméně dvě různé instance MyCMS, tak přihlášením do jednoho admin.php jsem přihlášen do všech, i když ten uživatel tam ani neexistuje

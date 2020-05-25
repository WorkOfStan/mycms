MyCMS
-----

Simple framework to help developing interactive websites. Works as a devstack which you install and then write your classes specific for the project.

[![Total Downloads](https://img.shields.io/packagist/dt/godsdev/mycms.svg)](https://packagist.org/packages/godsdev/mycms)
[![Latest Stable Version](https://img.shields.io/packagist/v/godsdev/mycms.svg)](https://packagist.org/packages/godsdev/mycms)

# Features
- [jQuery](https://jquery.org/) and [Bootstrap](https://getbootstrap.com/docs/4.0/components/) (version 4) used in the presentation
- [Latte](http://latte.nette.org/) used as a templating engine
- [MySQL](https://dev.mysql.com/)/[MariaDB](http://mariadb.com) used as the website database
- includes a general administration
- other used libraries and technologies - [Tracy](https://github.com/nette/tracy), [Nette\SmartObject](https://doc.nette.org/en/3.0/smartobject), [Psr\Log\LoggerInterface](https://www.php-fig.org/psr/psr-3/), [GodsDev\Backyard\BackyardMysqli](https://github.com/GodsDev/backyard/blob/master/GodsDev/Backyard/BackyardMysqli.php)

# Installation
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
and most of them are use prefix `My`.

To customize the project, create your own classes as children inheriting MyCMS' classes in the `./classes/` directory and name them it without the initial `My` in its name.  

```php
$MyCMS = new \GodsDev\MyCMS\MyCMS(
    array(
        // compulsory
        'logger' => $logger, // object \Psr\Log\LoggerInterface
        //optional
    )
);

//Finish with Latte initialization & Mark-up output
$MyCMS->renderLatte(DIR_TEMPLATE_CACHE, "\\GodsDev\\ProjectName\\Latte\\CustomFilters::common", $params);
```

Files `process.php` and `admin-process.php` MUST exist and process forms.

Note: `$MyCMS` name is expected by `ProjectSpecific extends ProjectCommon` class (@todo replace global $MyCMS by parameter handling)

# Deployment
## `/dist`
Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder in order to easily start.
Replace the string `MYCMSPROJECTNAMESPACE` with your project namespace.
Replace the string `MYCMSPROJECTSPECIFIC` with other website specific information (Brand, Twitter address, phone number...).

MyCMS is used only as a library, so the application using it SHOULD implement `RedirectMatch 404 vendor\/` statement as proposed in `dist/.htaccess` to keep the library hidden from web access.

## Languages
Following settings are expected from the Application that uses MyCMS
```php
define('DEFAULT_LANGUAGE', 'en');
```
Following files are expected to exist within the Application
* './language-' . $resultLanguage . '.inc.php';
where `$resultLanguage` is a (ISO 3166-2) two-letter language code.
Language versions (or translations, resp.) are specified when instatiating the MyCMS object. For example:
```php
array(
    ...
    'TRANSLATIONS' => array(
        'en' => 'ENG',
        'cn' => '中文',
        'cs' => 'CZ'
    ),
)
```

# Admin notes
## clientSideResources
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

# Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

Note that `dist` folder contains the starting MyCMS based project deployment and testing runs through `dist` as well, 
so for development, the environment has to be set up for `dist` as well. 

# TODO

* 190705: v classes\LogMysqli.php probíhá logování `'log/sql' . date("Y-m-d") . '.log.sql');` do aktuálního adresáře volajícího skriptu - což u API není výhodné. Jak vycházet z APP_ROOT?
* 190723: pokud jsou v té samé doméně dvě různé instance MyCMS, tak přihlášením do jednoho admin.php jsem přihlášen do všech, i když ten uživatel tam ani neexistuje
* TO BE CHECKED 190723: nastavování hesla by se nemělo do log.sql ukládat - volat instanci BackyardMysqli namísto LogMysqli?? @crs2: Řešilo by to přidání parametru (do query() v LogMysqli.php), který by volání error_log() potlačil? A poté u změny hesla volání tohoto parametru? + Ještě mě napadá řešení na úrovni samotného sloupce tabulky, tj. definování (v LogMysqli.php), které sloupce které tabulky obsahují citlivé údaje pro logování. Ale to by vyžadovalo parsing SQL.
* 200314: administrace FriendlyURL je v F4T/classes/Admin::outputSpecialMenuLinks() a ::sectionUrls() .. zobecnit do MyCMS a zapnout pokud FRIENDLY_URL == true
* 200526, CMS: * 200526: If Texy is used (see only in MyTableAdmin `($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')` then describe it. Otherwise remove it from composer.json, Latte\CustomFilters\, ProjectCommon, dist\index.php.

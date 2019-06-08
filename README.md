MyCMS
-----

Simple framework to help developing websites. Works as a devstack which you install and then write your classes specific for the project.

# Features
- [jQuery](https://jquery.org/) and [Bootstrap](https://getbootstrap.com/docs/4.0/components/) (version 4) used in the presentation
- [Latte](http://latte.nette.org/) used as a templating engine
- [MySQL](https://dev.mysql.com/)/[MariaDB](http://mariadb.com) used as the website database
- includes a general administration
- other dependent libraries and technologies - Tracy, Nette\SmartObject, Psr\Log\LoggerInterface, GodsDev\Backyard\BackyardMysqli

# Installation
Require MyCMS in [`composer.json`](https://getcomposer.org/).
```json
{
    ...
    "required": {
        "GodsDev/mycms": "^0.3.4" //or the latest version
        ...
    }
}
```
The `composer install` command will load the library's files into `./vendor/godsdev/mycms/`. The library's classes are in `./vendor/godsdev/mycms/classes/` and most of them are named to begin with `My`.

To customize the project, create your own classes as children inheriting MyCMS' classes in the `./classes/` directory and name it without the initial `My` in its name.  

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

Note: `$MyCMS` name is expected by `ProjectSpecific extends ProjectCommon` class (@todo replace global $MyCMS by proper parameter handling)

# Deployment
## `/dist`
Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder.
Replace the string `MYCMSPROJECTNAMESPACE` with your project namespace.
Replace the string `MYCMSPROJECTSPECIFIC` with other website specific information (Brand, Twitter address, phone number...).

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

`admin.css` may be inherited to a child project, however as vendor folder has usually denied access from browser, the content of that standard `admin.css` MUST be available through method MyAdmin::getAdminCss.

# Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

# @todo
* 180221: curate `dist` folder so that it may be used out of the box

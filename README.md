MyCMS
-------------------------------------

Simple framework for website generation.

It uses Latte for templates.

# Initiation

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

Folder `dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder.
Replace the string `MYCMSPROJECTNAMESPACE` with your project namespace.
Replace the string `MYCMSPROJECTSPECIFIC` with other website specific.

Following settings are expected from the Application that uses MyCMS
```php
define('DEFAULT_LANGUAGE', 'en');
```

Following files are expected to exist within the Application
* './language-' . $resultLanguage . '.inc.php';

# Admin notes

`admin.css` may be inherited to a child project, however as vendor folder has usually denied access from browser, the content of that standard `admin.css` MUST be available through method MyAdmin::getAdminCss.


# Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

# @todo
* 180221: curate `dist` folder so that it may be used out of the box

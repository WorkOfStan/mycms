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

Note: $MyCMS name is expected by ProjectSpecific extends ProjectCommon (@todo replace global $MyCMS by proper parameter handling)

# Deployment

Following settings are expected from the Application that uses MyCMS
```php
define('DEFAULT_LANGUAGE', 'en');
```

Following files are expected to exist within the Application
* './language-' . $resultLanguage . '.inc.php';


# Testing

Run from a command line:
```sh
./vendor/bin/phpunit
```

# @todo


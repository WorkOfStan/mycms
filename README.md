MyCMS
-------------------------------------

Simple framework for website generation.

It uses Latte for templates.


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


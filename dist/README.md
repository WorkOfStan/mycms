# MYCMSPROJECTNAME
XYZ web

# Stack

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


# Content



# Web analytics

Gtag version may be used only after https://github.com/googleanalytics/autotrack is updated to work with it.
I.e. probably when it will be out of beta.
script/autotrack.V.V.V.js and script/autotrack.V.V.V.js.map are manually taken from current (v2.4.1) https://github.com/googleanalytics/autotrack repository.

to-be-done - as GA events

* Production: UA-XYZ
* Test: UA-39642385-1


# Deployment

Create database with `Collation=utf8_general_ci`

Create `conf/env_config.local.php` based on `env_config.local.dist.php`

`composer update`

Create database with `Collation=utf8_general_ci`

Note: All changes in database (structure) SHOULD be made by phinx migrations. Create your local `phinx.yml` as a copy of `phinx.dist.yml` to make it work, where you set your database connection into *development* section. 
```bash
vendor/bin/phinx migrate -e development # or production or testing
```


Under construction mode may be turned on (for non admin IP adresses i.e. not in `$debugIpArray`) by adding
```php
define('UNDER_CONSTRUCTION', true);
```
to `conf/env_config.local.php`. 

Note: Management má iPhone a Mac - testovat na Apple prostředí!

## reCAPTCHA

Paste this snippet at the end of the <form> where you want the reCAPTCHA widget to appear:
```html
<div class="g-recaptcha" data-sitekey="................"></div>
```

# CMS notes


## Asset folder structure
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

# Debugging

Pro výpis proměnné nebo exception do `Tracy` použij:
```php
\Tracy\Debugger::barDump($mixedVar);
```

Pro výpis proměnné do logu použij:
```php
\Tracy\Debugger::log($stringVar, 'DEBUG');
```

`$debugIpArray` in `env_config.php` contains IPs for Tracy.


# Error handling

If your IP is among `$debugIpArray` you will see an Exception on screen. Otherwise, you will get "nice" Tracy 500 Internal server error.

Logs are in folder `log`:
* `exception.log` contains fatal errors by Tracy\Debugger
* `error.log` contains recoverable erros by Tracy\Debugger
* `debug.log` contains debug info by Tracy\Debugger
* `backyard-error.log.YYYY-MM.log` by PSR-3 logger implemented in GodsDev\Backyard\BackyardError
* `sqlYYYY-MM-DD.sql` contains content changes by CMS as SQL statements with timestamp

# Templating

`latte` templates are used.



# Visual style

Pages have view-TEMPLATE class in <body/> to allow for exceptions.

Convert SASS to CSS by
```sh
sass styles/index.sass styles/index.css
```


# TODO


## TODO lokalizace


## TODO CMS


## TODO SEO


## TODO vizualizace


## TODO other


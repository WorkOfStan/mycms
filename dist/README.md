# MYCMSPROJECTSPECIFIC
XYZ web
(Folder *dist* is an instant seed of new project.)

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

# MyCMS dist deployment
* Folder `/dist` contains initial *distribution* files for a new project using MyCMS, therefore copy it to your new project folder.
* Replace the string `mycmsprojectnamespace` with your project namespace.
* Replace the string `MYCMSPROJECTSPECIFIC` with other website specific information (Brand, Twitter address, phone number, database name, name of icon in manifest.json etc.).
* Default *admin.php* credentials are *john* / *Ew7Ri561*   - MUST be deleted after the real admin account is set up.
* Delete this section after the changes above are made

# Deployment

Create database with `Collation=utf8_general_ci`

Create `conf/config.local.php` based on `config.local.dist.php` including the name of the database created above

Create `phinx.yml` based on `phinx.dist.yml` including the name of the database created above

Under construction mode may be turned on (for non admin IP adresses i.e. not in `$debugIpArray`) by adding
```php
define('UNDER_CONSTRUCTION', true);
```
to `conf/config.local.php`. 

Note: Management má iPhone a Mac - testovat na Apple prostředí!

## `build.sh` runs the following commands

`composer update`

Note: All changes in database (structure) SHOULD be made by phinx migrations. Create your local `phinx.yml` as a copy of `phinx.dist.yml` to make it work, where you set your database connection into *development* section. 
```bash
vendor/bin/phinx migrate -e development # or production or testing
```

## reCAPTCHA

Paste this snippet at the end of the <form> where you want the reCAPTCHA widget to appear:
```html
<div class="g-recaptcha" data-sitekey="................"></div>
```

# CMS notes

## Agenda
Agenda is an item in the admin.php menu that refers to a set of rows in database. (TODO: be more specific)

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

### admin.php expects
* scripts\bootstrap.js
* scripts\admin-specific.js
* scripts\ie10-viewport-bug-workaround.js
* scripts\summernote.js
* styles\bootstrap.css
* styles\bootstrap-datetimepicker.css
* styles\font-awesome.css
* styles\ie10-viewport-bug-workaround.css
* styles\summernote.css
* fonts\fa*.*

# Debugging

Pro výpis proměnné nebo exception do `Tracy` použij:
```php
\Tracy\Debugger::barDump($mixedVar);
```

Pro výpis proměnné do logu použij:
```php
\Tracy\Debugger::log($stringVar, 'DEBUG');
```

`$debugIpArray` in `config.php` contains IPs for Tracy.

## REST API

Note: header("Content-type: application/json"); in outputJSON hides Tracy

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
sass styles/index.sass styles/index.css  # made also by build.sh
```

When changing index.css, index.js or admin.js, update `PAGE_RESOURCE_VERSION` in `config.php` in order to force cache reload these resources.

# TODO

## TODO lokalizace


## TODO CMS


## TODO SEO


## TODO vizualizace


## TODO other
* 1812 až friendlyUrl součástí MyCMS, tak v .latte zrušit {dirname($_SERVER['SCRIPT_NAME'])} a dát místo toho applicationDir
* 190611 add article and search page types including controller tests
* 190611 Make SASS to CSS conversion automatic (e.g. gulp)

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### `Added` for new features

### `Changed` for changes in existing functionality

- chore: use secure version of `godsdev/tools` library

### `Deprecated` for soon-to-be removed features

### `Removed` for now removed features

### `Fixed` for any bugfixes

### `Security` in case of vulnerabilities

## [0.5.0] - 2026-02-14

fix: remove support below PHP/7.2, because of CVE-2026-24765

### Added

- add `"swiftmailer/swiftmailer": "^6.3"` to composer.json, so that `dist/` is valid during PHP Static analysis
- ci: bump GitHub Actions

### Changed

- PHPUnit_Framework_TestCase to PHPUnit\Framework\TestCase

### Fixed

- minor code tweeks to make the code stricter
- fix: remove parameter typing in `LogMysqli::query()` in order to be backward compatible

### Security

- remove support below PHP/7.2, because of CVE-2026-24765 - High-severity PHPUnit Vulnerable to Unsafe Deserialization in PHPT Code Coverage Handling

## [0.4.10] - 2024-08-03

feat: BackyardError wraps seablast-logger implementation

### Added

- ignore style imperfections in (third-party) JavaScripts

### Changed

- use Backyard::^3.4.0 where BackyardError wraps \Seablast\Logger\Logger implementation

### Removed

- VERSION file as git-flow branching model is no longer used
- `"texy/texy": "^2.7.1"` is no longer used in MyCMS

### Fixed

- bump versions of GitHub workflow actions

## [0.4.9] - 2023-06-16

feat: php-composer-dependencies-reusable.yml OPTIONAL config.local.dist.php

### Changed

- php-composer-dependencies-reusable.yml: Copy config.local.dist.php only if present
- dist/build.sh: Copy config.local.dist.php only if present. Warnings are red.
- htmlOption(scalar $value,...)

## [0.4.8] - 2023-03-09

feat: GA4 Analytics, stricter parameter handling

### Added

- GA4 Analytics as a new alternative for soon to be obsoleted (2023-07-01) Google Universal Analytics. Empty GA_UID or GA4_STREAM will result in not using the respective JavaScript code.
- added declaration of @throws in PHPDoc in ThrowablePHPFunctions.php

### Changed

- Database tests should fire up only if phinx.dist.yml is present
- PHPUnit tests should fire up only if conf/phpunit-github.xml is present
- solve stricter analysis by assertion, typecasting and type definition

### Removed

- dist/rector.php (use just string replace instead of the rector engine)

### Fixed

- Exception rendering Admin Modules Division and products, Pages, Products, Translations, URL when 'admin_latte_render' => true
- dist/Controller.php: added Assertion to fix Error: Property WorkOfStan\MyCMS\MyCMSMonoLingual::$context (array<array|int|string|false|null>) does not accept non-empty-array<mixed>. && Error: Parameter #2 $search of function array_key_exists expects array, mixed given.
- dist/Admin.php: if the product or content piece doesn't have a label in admin language show an explanation instead of null

### Security

- Legacy (non admin_latte_render) admin UI block all administration pages if not logged-in

## [0.4.7] - 2022-07-17

feat!: Admin UI can be rendered by Latte & filtering and sorting of tables fixed

- fix filtering and sorting of tables in Admin UI
- Admin UI can be rendered by Latte (instead directly from MyAdmin methods) if $featureFlags['admin_latte_render'] set to true (still experimental because the main part of body is prerendered as HTML)
- DivisionProducts, Pages, Products, Translations, `Urls` admin pages are generated in AdminModel classes invoked within Admin::controller (instead as spaghetti code within Admin::projectSpecificSections) (Feature flag 'legacy_admin_methods_instead_of_admin_models' => true forces execution of the old spagetti code)
- where Translations and `Urls` admin page generation is moved to the the core so that further improvements are automatically available to the Apps using updated version of MyCMS
- **BREAKING CHANGE (feature flagged)** if $featureFlags['admin_latte_render']===false `dist/styles/admin.css.php` MUST be present (to link rel `admin.css` deep in vendor folder) and admin.php code has to be updated to alternatively use the latte rendering and toggle admin menu special links

### Added

- admin-de.yml language file for German variant of Admin UI
- LogMysqli: add several `$this->showSqlBarPanel();` statements in order to display SQL statements in case of Exception
- class Render to take care of Latte rendering both for App UI and Admin UI
- App can use either inherited Latte templates or override it with modified templates
- Latte inheritance test pages (`inheritance-*.latte` added where new include inherite.latte is demonstrated) including PHPUnit FriendlyUrlTest::testPageStatusOverHttp
- instead of simply {include $latte} call {include 'inherite.latte', latte => $latte} so that the preferred existing version of latte is used
- new parameter to MyCustomFilters allows for another translate method (in order to use $tableAdmin->translate instead of $MyCMS->translate for Admin UI)
- featureFlags to `admin-*.latte`
- DivisionProducts, Pages, Products, Translations, `Urls` admin pages are generated in AdminModel classes invoked within Admin::controller (instead as spaghetti code within Admin::projectSpecificSections) (Feature flag 'legacy_admin_methods_instead_of_admin_models' => true forces execution of the old spagetti code)

### Changed

- **BREAKING CHANGE (feature flagged)** if $featureFlags['admin_latte_render']===false `dist/styles/admin.css.php` MUST be present (to link rel `admin.css` deep in vendor folder) and admin.php code has to be updated to alternatively use the latte rendering and toggle admin menu special links
- MyCommon::verboseBarDump Dumper::LOCATION => false hides where the dump originated as this is not the original place anyway, shows it in the title instead
- nicer formatting Admin UI table SQL statement
- Logging of untranslated strings when `DEBUG_VERBOSE` into monthly rotating `log/translation_missing_{date("Y-m")}.log` (instead of one big swelling translation_missing.log)
- Admin UI can be rendered by Latte (instead directly from MyAdmin methods) if $featureFlags['admin_latte_render'] set to true (still experimental because the main part of body is prerendered as HTML)
- Admin UI in Latte mode receives params including token and authUser (0=anonymous, 1=logged-in)
- Default Latte templates moved from app part of templates to library part of templates in order to quickly deploy. If you start working with the templates however, you should maintain them in the app folder.
- color highlighting of sections of scripts build.sh and phpstan.sh
- relax dist/FriendlyUrl::switchParametric - if there's no rule to produce a friendly URL, don't report error not changing it, only an info
- dist/composer.json: Keeps packages sorted by name when adding new one
- GitHub Actions: only use cache with an exact key hit
- MyAdmin::renderAdmin() $switches as parameter of Latté instead of working with `$\_GET` in the template
- MyAdmin table view is rendered by Latte
- MyAdmin `$_GET` changed to $this->get
- MyCMSMonoLingual::fetchAndReindexStrictArray (softly) moved to LogMysqli::fetchAndReindexStrictArray
- Translations and `Urls` admin page generation is moved to the the core so that further improvements are automatically available to the Apps using updated version of MyCMS

### Deprecated

- dist/rector.php (todo consider using just string replace instead of the rector engine)
- Admin UI in Latte mode doesn't use Admin::endAdmin(), `Admin::getAdminCss()`, Admin::outputAdmin(), Admin::outputBodyEnd(), Admin::outputFooter(), Admin::outputHead(), Admin::outputImageSelector(), Admin::outputNavigation(), Admin::outputSpecialMenuLinks(), Admin::outputSpecialSettingsLinks(), so before turning on the Admin Latte, move the custom code to the corresponding lattes

### Removed

- attributes type and charset (as in `<script type="text/javascript" src="scripts/admin.js?v=1" charset="utf-8">`) are obsolete

### Fixed

- SQL syntax: TAB_PREFIX may need backticks around its name
- fix filtering of tables in Admin UI (Assert::string instead of integer in MyTableLister)
- fix sorting of tables in Admin UI with foreign-link

## [0.4.6] - 2022-03-05

refactor!: Less files in the application root folder

### Added

- dist: subscriber table, therefore subscription input box is working now
- dist: featureFlag `newletter_input_box` can turn off both the (un)subscribe email input box and the POST value processing

### Changed

- **BREAKING CHANGE** dist/Test/AdminTest.php TableAdmin declaration expects language and prefixL10n
- **BREAKING CHANGE** dist/Test/ControllerTest.php MUST accomodate language both in GET and `_SESSION` as the checks are stricter in L10n
- App class handles the request dispatching instead of spagetti code in index.php
- L10n (Localisation) class with loadLocalisation and translate methods common both for admin UI and MyCMS UI (instead of include php file with array for web UI and parsing yml for admin UI)
- dist/Admin::sectionTranslations uses new L10n class instead of including language.inc.php file directly
- MyAdminProcess::processTranslationsUpdate method created instead of code being spagetti part of dist/AdminProcess::adminProcess

### Deprecated

- using dist/language-xx.inc.php files (conf/l10n/language-xx.yml will be used instead) - just add $myCmsConf['prefixL10n'] to config.php and resave admin.php?translations to transform to the new format (unless turned off by featureFlag 'languageFileWriteIncOnlyNotYml')

### Removed

- Texy
- dist/process.php moved to dist::App class as part of run method
- files dist/language-xx.inc.php were removed from the seed app, but are still supported by the MyCMS/dist (see deprecated section)

### Fixed

- yml style, add missing document start "---" (document-start); fix indentation
- dist/footer.latte Colon as argument separator is deprecated, use comma instead

### Security

- App class changes touched the CSRF mechanism, so CSRF was successfully tested

## [0.4.5] - 2022-02-10

refactor!: PHPStan level=9

### Added

- phpstan.sh and phpstan-remove.sh for local testing
- phpstan-baseline.neon to hide type hint imperfections in PHPStan level=8 (todo fix these)
- LogMysqli::fetchSingleString for strict string response
- LogMysqli::fetchStringArray for string[] response
- MyCommon::verboseBarDumpString for strict string response
- ArrayStrict to Enforce type strictness of array fields (originally <https://github.com/WorkOfStan/stockpiler/blob/main/classes/ArrayStrict.php>)
- ArrayStrict::arrayString to asserts that all values of the array are of string type
- MyCMSMonoLingual::setContext in order to enforce the type
- MyTools::h(mixed) - Tools::h that accepts mixed but throws exception if it isn't a string
- MyController::run getter for template and context to narrow the type hints
- MyCMSMonoLingual::fetchAndReindexStrictArray refactored to return the expected array structure
- MyCMSMonoLingual::fetchAndReindexStrictArray displays the SQL bar panel in case of Assertion thrown Exception

### Changed

- **BREAKING CHANGE** MyFriendlyUrl::determineTemplate change from `@return string|array<string>|true` to `@return string|array<int|string>|true`
- **BREAKING CHANGE** dist/FriendlyUrlTest.php `Assert::string($templateDetermined['redir']);` added where needed
- instead of dist specific workflows, use reusable ones (linter.yml, overtrue-phplint.yml, php-composer-dependencies.yml, phpcbf.yml) Note: only scalar value can be passed as input to a reusable workflow input - not an array of strings.
- VALIDATE_GITHUB_ACTIONS: false as it returns false positive `unknown Webhook event "workflow_call"` (todo reconsider later)
- MyTableLister::bulkUpdateSQL passes $vars by value (it's not necessary to pass by reference)
- Tools::setifnull and Tools::set replaced by a type hint strict code (avoid `cannot cast mixed to string` warning)
- changed type hinting mixed to something more specific
- refactoring ProjectSpecific::getContent
- LogMysqli::fetchStringArray NULL is replaced by empty string
- dist/set-environment.php moved to dist/conf/set-environment.php
- $this->verboseBarDump returns mixed, so taken out of return statements (for smooth static analysis)
- MyTableLister::bulkUpdateSQL passes $vars by value (it's not necessary to pass by reference)
- $myCmsConf is mixed[] (instead of complex and not future usage proof array<array<array<array<string>,bool,string>,string>|BackyardError|LogMysqli|null> ) For some fields, there is a runtime assertion.
- workofstan/backyard bumped to ^3.3.1
- ./github/workflows/overtrue-phplint.yml Bump overtrue/phplint from 3.0.3 to 4.1.0
- ./github/workflows/phpcbf.yml Bump actions-x/commit from 2 to 4

### Removed

- GitHub automated test rector/rector (todo return when rector ready for phpstan:1.0)

### Fixed

- some type hint imperfections (especially for nested arrays) in [PHPStan level=8](https://github.com/phpstan/phpstan/releases/tag/1.0.0)
- some Assertions changed to conditions to test only relevant scenarios and thus prevent Exceptions generated by stricter Assertions
- uncommented dist/conf/config.php PATH_CATEGORY as it is expected in Admin::projectSpecificSections
- various conditions changed to reflect type hinting required by PHPStan level=9
- changed SQL table fields in Admin::projectSpecificSections, Admin::sectionDivisionsProducts to comply to the dist app tables

## [0.4.4] - 2021-10-04

fix: Fix admin table view empty

- fixed MyTableLister::view ternary operator blocking non-empty table output
- faster dist seed app creation (rector.php + build.sh)

### Added

- dist/build.sh: if not exists create conf/config.local.php + phinx.yml
- Faster MyCMS dist deployment by rector.php (GitHub online check include composer require --dev rector/rector)
- dist/permissions.sh: run if necessary to allow web server user write into cache and log folders

### Changed

- VALIDATE_GITLEAKS: false because of false positive (todo reconsider later)
- dist: VALIDATE_GITLEAKS: false because of false positive
- change github/super-linter master -> main

### Removed

- unnecessary `sql =` constructs removed

### Fixed

- MyTableLister::view ternary operator blocking non-empty table output
- dist: GitHub online composer require --dev rector/rector
- dist/classes/TableAdmin.php - line 177: Parameter #2 $multiplier of function str_repeat expects int, float|int<-1, max> given.

## [0.4.3] - 2021-07-27

ci: MyCMS::InitDatabase + PHPUnit tests on GitHub

- MyCMS::InitDatabase class to read database configuration from (dist/)phinx.yml
- GitHub automation improved

### Added

- ThrowableFunctions/ThrowablePHPFunctions::strtotime
- `phpcbf*` branch (no slash allowed) triggers commit of auto PHPCS fixes, controlled by [phpcbf.yml](.github/workflows/phpcbf.yml)
- InitDatabase class to read database configuration from (dist/)phinx.yml
- cache for composer downloaded libraries into vendor folder for GitHub automated testing
- added MySQL/8 (part of Ubuntu/20 virtual environment) with native password access used by phinx::testing environment
- Note: Apache/2 that is part of Ubuntu/20 virtual environment doesn't have PHP installed by default, so phpunit @group webserver is excluded from automatic GitHub Actions testing

### Changed

- to Check PHP syntax errors uses: overtrue/phplint@3.0.3 (instead of obsoleted michaelw90/PHP-Lint@master)
- dist/build.sh uses composer update -a **--prefer-dist --no-progress**

### Fixed

- MyTableAdmin::outputField case timestamp $value doesn't have to be string
- phpcbf automatically fixed style in styles/admin.css, dist/styles/admin.css, dist/scripts/admin-specific.js, dist/scripts/admin.js, dist/classes/Admin.php
- assert array as argument of array_shift in LogMysqli::fetchAndReindex
- in dist/images/favicon/manifest.json 12x error Unnecessary escape character: \/ jsonc/no-useless-escape
- fix classes/MyAdmin.php line 640: Ternary operator condition is always true.
- added private ProjectCommon::assertStringArray to fix Parameter #1 $pattern of function WorkOfStan\MyCMS\ThrowableFunctions\preg_replaceString expects array<string>|string, array<int, (int|string)> given.

### Security

- dist/.htaccess: no Markdown file can be available on web

## [0.4.2] - 2021-05-28

refactor!: PHPStan level=max ... Error Zero

### Added

- [conf/phpstan.webmozart-assert.neon](conf/phpstan.webmozart-assert.neon) to allow for `phpstan --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M` in GitHub automated testing
- LogMysqli::queryStrictNonEmptyArray
- MyCMSMonoLingual::fetchAndReindexStrictArray
- ThrowableFunctions/ThrowablePHPFunctions.php preg_match_all
- many assertions and few type castings and queryStrictObject (instead of query) to eliminate PHPStan level max errors

### Changed

- **potentially breaking change** MyTableAdmin::outputForm, MyTableLister::pagination, MyTableLister::view, MyTableLister::viewInputs, MyTableLister::viewTable - ignores not-presence of parameter $options['return-output'] and always returns string, never echo string (would result @return mixed void or string issue)
- MyTableLister::customInputBefore and MyTableLister::customInputAfter - parameter 2 is mixed type (not a string)
- MyTableAdmin::outputField case:timestamp replaced old way of addressing characters in a string `$value[10]` by more modern `substr($value, 10, 1)`

### Fixed

- dist ProjectSpecific::getContent,::getCategory - code|ID are used by the SQL statement only if they are present
- MyAdminProcess::processUserCreate $salt typecasted as string
- MyTableAdmin::recordSave and MyTableAdmin::recordDelete - resolveSQL call treated $messageSuccess and $messageError as strings while they are bool

## [0.4.1] - 2021-05-26

refactor!: Dist: api/noun construct replaces separate code in api/noun folders

### `Added`

- dist HTTP POST BarPanel for Tracy (both web and admin)

### `Changed`

- api/noun (api/noun/) construct is used instead of separate code in api/noun folders (therefore also sql logs are created in a stadard folder e.g. log/sql*.log.sql instead of within api/apiName/log/sql*.log.sql)
- dist `notest/*` branch doesn't trigger GitHub Action tests
- dist/process.php `$_POST['newsletter']` related process uses new $sessionWriteClose parameter 3 of method Tools::redir, that turns off `session_write_close();` so that e.g. Tracy gets info about redirect.

## [0.4.0] - 2021-05-21

feat!: MyFriendlyURL is routing of Controller + ready-made dist boilerplate

- Release v0.4.0 is expected to be mostly compatible with v0.3.15, but due to sheer volume of changes, let's not mark it as only a patch.
- **Breaking change**: GodsDev\Backyard switched to WorkOfStan\Backyard - so either the namespace should be changed or original GodsDev\Backyard required instead as temporary fix.

### Added

- `notest/*` branches ignored by GitHub Actions (not to test partial online commits)
- Throwable/ThrowablePHPFunctions.php - replacement for PHP functions that returns false or null instead of the strict type. These functions throw an \Exception instead.
  - filemtime, glob, json_encode, mb_eregi_replace, preg_match, preg_replace
  - preg_replaceString accepts only string as $subject and returns only string (i.e. not string[])
- dist/admin.php: HTTP POST panel
- LogMysqli::queryStrictObject Logs SQL statement not starting with SELECT or SET. _Throws exception in case response isn't `\mysqli_result`_
- LogMysqli::queryStrictBool Logs SQL statement not starting with SELECT or SET. _Throws exception in case response isn't `true`_
- type hints (especially array iterable value types)
- CHANGELOG.md
  - .markdown-lint.yml (to ignore same headings in CHANGELOG) replaces the default one, hence SHOULD include the original settings
- '5.6', '7.0', '7.2', '7.3', '7.4' added to PHPStan matrix
  - not 7.1 as due to <https://bugs.php.net/bug.php?id=73803> ZipArchive class has public properties, that are not visible via reflection.
  - Therefore using tools like PHPStan generates error: Access to an undefined property ZipArchive::$numFiles. in class\MyAdminProcess
- GitHub Action job running time limited to 10 minutes
- dist phpstan includes phpstan/phpstan-webmozart-assert, therefore other PHPStan configuration file added in order to include proper extension.neon
- PHPStan online runs as a tool (not a composer required-dev library)
- If DEBUG_VERBOSE is true and admin UI uses untranslated string, it is logged to `log/translate_admin_missing.log` to be translated
- dist/TableAdmin templates for methods customInput and customInputAfter added
- dist/Admin outputSpecialMenuLinks and projectSpecificSections etc. overrides of MyAdmin methods added
- featureFlags work also in Admin UI
- Admin.php, AdminProcess.php, admin.js and `admin.css` now contains (almost) all the code from A and F projects - some is however not working (hence featureFlag 'order_hierarchy') TODO: simplify it and keep only the essential
- Admin UI: Friendly URL: one place to set them all, identify duplicities
- Admin UI: generate translations. Note: this rewrites the translation files language-xx.inc.php
- admin.js: toggle Export, Edit, Clone buttons based on row selection
- Tracy SQL BarPanel is red if some of the SQL statements end up in an error state. The failed one is prefixed by `fail =>`
- MyAdminProcess::processUserChangePassword - ILogger::INFO of failed changing password
- MyCommon::verboseBarDump - Dumps information about a variable in Tracy Debug Bar or is silent
- MyController::redir - Redirects to $redir (incl. relative) and die
- MyController::run - spuštění, které používá Controller::prepareTemplate a Controller::prepareTemplateAll
- ProjectCommon::getLinkSql - Returns SQL fragment for column link ($fieldName) which construct either parametric URL or relative friendly URL
- ProjectCommon::language - Returns or set the language
- MyTableAdmin: fix if prefill value is not yet among the existing values, set as value for the own-value input box
- View: latte may use $applicationDirLanguage to keep the selected language folder
- View: FEATURE_FLAGS are available now in JavaScript
- class Mail incl. test UI: class Mail works both on PHP/5.6 (tested on 5.6.40-0+deb8u12) and PHP/7.x (tested on 7.3.27-1~deb10u1) with test page available at the third tab
- Recommendation: if you change boilerplate classes, update also info `(Last MyCMS/dist revision: 2021-05-20, v0.4.0)`, so that it is more clear what to update in case of MyCMS core upgrade.

### Changed

- **breaking change** namespace GodsDev\MyCMS to WorkOfStan\MyCMS
- **breaking change** namespace GodsDev\mycmsprojectnamespace to WorkOfStan\mycmsprojectnamespace
- WorkOfStan/MyCMS repository instead of GodsDev\MyCMS repository
- Dist code: vast changes in order to have working application ready to install including data structure of pages and products including friendly URL routing in 4 languages (cs,de,en,fr) and redirector
- MyFriendlyURL is a new routing part of Controller (of MVC) (Examples of behaviour explained in dist/README.md#seo are implemented in dist)
- Controller is set by Controller::prepareTemplate a Controller::prepareTemplateAll and called by MyController::run (instead of MyController::controller, which still remains for backward compatibility)
- nette/utils allowed also in version ^3.2.2
- godsdev/tools bumped to type strict version ^0.3.8
- LogMysqli::fetchSingle Throws excepetion, when an SQL statement returns true.
- LogMysqli::fetchAndReindex Error for this function is also an SQL statement that returns true.
- MyAdminProcess::redir make use of new option in GodsDev\Tools::redir and turns off `session_write_close()` in order to pass info about redirect to Tracy
- MyCMSMonoLingual if logger is not passed to the class, constructor will throw an Exception (instead of die)
- MyFriendlyURL::friendlyIdentifyRedirect throws Exception on seriously malformed URL
- MyTableAdmin::outputField fixesParameter 2 $label of static method GodsDev\Tools\Tools::htmlInput() expects string, false given. MUST be empty string to trigger label omitting.
- MyTableAdmin methods (recordDelete) refactoring for better readability
- MyTableAdmin: removed zero value that is not accepted by ENUM. To set empty, use NULL option. Todo fix NULL option for ENUM to be saved in database
- MyTableLister::resolveSQL refactoring for better readability
- **potentially breaking change**: MyTableLister::contentByType ignores missing $options['return-output'] and always return string, never echo string
- **potentially breaking change**: MyTableLister: filterKeys accepts strictly array<string>
- ProjectCommon::localDate throws Exception if $stringOfTime is malformed
- dist/AdminProcess::getAgenda refactoring for better static analysis
- dist/Controller instatiates Mail only if `class_exists('Swift_SmtpTransport')` so that PHPUnit test run from root doesn't stupidly fail
- dist/ProjectSpecific::getSitemap throws Exception if sitemap retrieval fails
- dist/TableAdmin loads localised strings from conf/l10n/admin-XX.yml
- use only phinx.yml environment for database connection (instead of duplicating it in config.local.php)
- Bootstrap v4.0.0-beta -> Bootstrap v4.1.3, which is the highest version that works properly with Summernote 0.8.18 (background under buttons, but Full screen doesn't use background)
- jQuery v3.2.1 -> jQuery v3.6.0
- proper attribution: copyright of Admin UI to WorkOfStan & CRS2 (instead of GODS)
- **potentially breaking change** admin.js: `let` when initializing variable
- changed DATETIME (datatype) to TIMESTAMP for
  a) better compatibility as the DEFAULT CURRENT_TIMESTAMP support for a DATETIME (datatype) was added in MySQL 5.6. In 5.5 and earlier versions, this applied only to TIMESTAMP (datatype) columns.
  b) MySQL converts TIMESTAMP values from the current time zone to UTC for storage, and back from UTC to the current time zone for retrieval. (This does not occur for other types such as DATETIME.)”.
  - An important difference is that DATETIME represents a date (as found in a calendar) and a time (as can be observed on a wall clock), while TIMESTAMP represents a well defined point in time. This could be very important if your application handles time zones.
- Coding style: <https://www.php-fig.org/psr/psr-12/> replaces PSR-2
- Coding style: array() to [] (As of PHP 5.4)
- Many elements ordered alphabetically for better readability
- `return;` statement after method with @return never is not necessary
- dist: favicons moved to images/favicon subfolder
- bump "godsdev/backyard": "^3.2.10" -> "workofstan/backyard": "^3.3.0" to use better tested code limited by php: ^5.3 || ^7.0
- MyController::run should be tested instead of the deprecated MyController::controller

### Fixed

- Stricter code by type assertion, type casting, type hinting
- Stricter code by return type specific LogMysqli::queryStrict methods and ThrowablePHPFunctions (i.e. Exception thrown instead of an unexpected type returned on error)
- PHPStan level=6 => Error Zero
- MyAdminProcess::processSubFolder fixed EXIF related condition so that if nothing is known $entry['info'] .= '';
- (maybe the bug wasn't present in 0.3.15) MyAdmin: fix tableAdmin prevails TableAdmin
- when adding new content, automatically uncheck the NULL checkbox + save NULL value of checkbox
- Admin::searchColumns searches within existing columns
- Admin UI: Export selected rows
- MyTableLister::selectSQL() should return array<string> but returns array<string, int|string>.
- MyTableLister::bulkUpdateSQL() should return string but return statement is missing.
- MyTableLister::contentByType() always returns string (as if $options['return-output'] === true). Performing echo is responsibility of the calling method.
- process.php: \_SESSION and \_POST attributes processing fixed
- dist require `"symfony/yaml": "^3.4.47|^4|^5|^6"` so that it is loaded not only as part of require-dev phpunit/phpunit
- MyController::run MUST work even without MyFriendlyUrl instance

### Deprecated

- MyTableAdmin::outputSelectPath() - is this function necessary?
- MyController::controller calls should be replaced by MyController::run calls

## [0.3.15] - 2020-05-02

### Fixed

- bulkUpdateSQL: fix `continue` to `break` (not to `continue 2`)
  [as since PHP 7.3.0 continue within a switch that is attempting to act like a break statement for the switch will trigger an E_WARNING.]

## [0.3.14] - 2020-05-02

### Changed

- classes Test in separate path so that `/godsdev/mycms/classes/Test/` are not part of `autoload_static.php`

## [0.3.13] - 2020-05-02

### Changed

- test related classes moved to autoload-dev section in order to prevent Ambiguous class resolution in `dist\autoload_static.php` from

```php
        'GodsDev\\mycmsprojectnamespace\\' =>
        array (
            0 => __DIR__ . '/../..' . '/classes',
            1 => __DIR__ . '/..' . '/godsdev/mycms/dist/classes',
        ),
```

to

```php
        'GodsDev\\mycmsprojectnamespace\\' =>
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
```

## [0.3.12] - 2020-04-20

### Fixed

- fix test classes namespace and use statements to be PSR-4 compliant

## [0.3.11] - 2020-03-14

- login and logout and various process fixes
- Folder dist is a seed of a new project and works out of the box (incl. favicons for various browsers)
- fix zobrazování tabulek s foreign key
- fix řada konstant v conf/config.php je zde v MyCMS zřejmě zbytečně - zakomentovány, aby PHPUnit fungovalo s dist
- testing: PHPunit tests both mycms and dist (aby se testovalo i jak se chová v jednoduchém nasazení projektu)
- změny v `dist/*` aby se lépe nasazovaly nové projekty, např. dist/build.sh as a fast deployment script
- MyCMS added to packagist <https://packagist.org/packages/godsdev/mycms>
- fix backtick doubling
- clean-up: `.htaccess` removed as MyCMS is used only as a library, so the application using it SHOULD implement `RedirectMatch 404 vendor\/` statement as proposed in `dist/.htaccess` to keep the library hidden from web access.
- security change: LogMysqli: $logQuery optional default logging of database changing statement can be (for security reasons) turned off by value false
- security change: MyAdminProcess: processUserChangePassword - password change is not logged
- fix processActivity: undefined variable $tab replaced by the existing $tabs
- MyFriendlyUrl was tested in A and F projects and it worked fine, so it may go to production

## [0.3.10] - 2019-01-31

- processFilePack(), processFileUpload and processSubfolder() now test class_exist('ZipArchive')
- MyTableAdmin.php - bugfix in recordSave()

## [0.3.9] - 2019-01-18

- MyAdmin.php - bugfix in getPageTitle(); minor edits
- MyAdminProcess.php - +processActivity()
- MyTableAdmin.php - referer as hidden input field in outputForm()
- MyTableLister.php - bugfix in selectSQL()

## [0.3.8] - 2018-12-12

- Merge branch 'develop' of <https://github.com/GodsDev/mycms> into develop with Conflicts: classes/MyTableAdmin.php

## [0.3.7] - 2018-10-08

- fix LogMysqli::fetchSingle
- new LogMysqli::values Extract data from an array and present it as values, field names, or pairs.
- admin: getPageTitle
- admin opraveno ukládání and small design fixes

## [0.3.6] - 2018-09-20

- fix environment dependant CAST(AS)

## [0.3.5] - 2018-09-20

- bump "godsdev/tools": "^0.3.1"

## [0.3.4] - 2018-05-27

- fix i to i transformation

## [0.3.3] - 2018-06-01

- ProjectCommon::correctLineBreak Replace spaces with \0160

## [0.3.2] - 2018-05-29

- fix submit rename (bug 6691)

## [0.3.1] - 2018-05-07

- some methods renamed

## [0.3.0] - 2018-05-07

- Lot of improvements
  - logging
  - Tracy
  - customized CSS and JavaScript
  - dist folder contains an example project using MyCMS

## [0.2.5] - 2017-11-24

- changed loadSettings separated from getSessionLanguage
- added database manipulation fetchAll, fetchSingle, recordSave
- changed TableAdmin.php manipulation with JSON columns
- update TableLister.php translations and formatting

## [0.2.4] - 2017-11-07

- +fetchSingle(), improved fetchAndReindex()

## [0.2.3] - 2017-11-05

- TableAdmin update recordSave and outputForm

## [0.2.2] - 2017-11-05

- options for project customization of getSessionLanguage
- TableAdmin and TableLister improvements

## [0.2.1] - 2017-10-12

- fix language in SQL statement

## [0.2.0] - 2017-10-12

- PSR-3 logger
- TableAdmin objectified
- getSessionLanguage is made universal (it also includes langauge files and some database fields)

## [0.1] - 2017-10-06

- Basic functions
- Basic structure

[Unreleased]: https://github.com/WorkOfStan/mycms/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/WorkOfStan/mycms/compare/v0.4.10...v0.5.0
[0.4.10]: https://github.com/WorkOfStan/mycms/compare/v0.4.9...v0.4.10
[0.4.9]: https://github.com/WorkOfStan/mycms/compare/v0.4.8...v0.4.9
[0.4.8]: https://github.com/WorkOfStan/mycms/compare/v0.4.7...v0.4.8
[0.4.7]: https://github.com/WorkOfStan/mycms/compare/v0.4.6...v0.4.7
[0.4.6]: https://github.com/WorkOfStan/mycms/compare/v0.4.5...v0.4.6
[0.4.5]: https://github.com/WorkOfStan/mycms/compare/v0.4.4...v0.4.5
[0.4.4]: https://github.com/WorkOfStan/mycms/compare/v0.4.3...v0.4.4
[0.4.3]: https://github.com/WorkOfStan/mycms/compare/v0.4.2...v0.4.3
[0.4.2]: https://github.com/WorkOfStan/mycms/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/WorkOfStan/mycms/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/WorkOfStan/mycms/compare/v0.3.15...v0.4.0
[0.3.15]: https://github.com/WorkOfStan/mycms/compare/v0.3.14...v0.3.15
[0.3.14]: https://github.com/WorkOfStan/mycms/compare/v0.3.13...v0.3.14
[0.3.13]: https://github.com/WorkOfStan/mycms/compare/v0.3.12...v0.3.13
[0.3.12]: https://github.com/WorkOfStan/mycms/compare/v0.3.11...v0.3.12
[0.3.11]: https://github.com/WorkOfStan/mycms/compare/v0.3.10...v0.3.11
[0.3.10]: https://github.com/WorkOfStan/mycms/compare/v0.3.9...v0.3.10
[0.3.9]: https://github.com/WorkOfStan/mycms/compare/v0.3.8...v0.3.9
[0.3.8]: https://github.com/WorkOfStan/mycms/compare/v0.3.7...v0.3.8
[0.3.7]: https://github.com/WorkOfStan/mycms/compare/v0.3.6...v0.3.7
[0.3.6]: https://github.com/WorkOfStan/mycms/compare/v0.3.5...v0.3.6
[0.3.5]: https://github.com/WorkOfStan/mycms/compare/v0.3.4...v0.3.5
[0.3.4]: https://github.com/WorkOfStan/mycms/compare/v0.3.3...v0.3.4
[0.3.3]: https://github.com/WorkOfStan/mycms/compare/v0.3.2...v0.3.3
[0.3.2]: https://github.com/WorkOfStan/mycms/compare/v0.3.1...v0.3.2
[0.3.1]: https://github.com/WorkOfStan/mycms/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/WorkOfStan/mycms/compare/v0.2.5...v0.3.0
[0.2.5]: https://github.com/WorkOfStan/mycms/compare/v0.2.4...v0.2.5
[0.2.4]: https://github.com/WorkOfStan/mycms/compare/v0.2.3...v0.2.4
[0.2.3]: https://github.com/WorkOfStan/mycms/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/WorkOfStan/mycms/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/WorkOfStan/mycms/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/WorkOfStan/mycms/compare/v0.1...v0.2.0
[0.1]: https://github.com/WorkOfStan/mycms/releases/tag/v0.1

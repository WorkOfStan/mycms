# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Throwable/ThrowablePHPFunctions.php - replacement for PHP functions that returns false or null instead of the strict type. These functions throw an \Exception instead.
  - filemtime, glob, json_encode, mb_eregi_replace, preg_match, preg_replace
  - preg_replaceString accepts only string as $subject and returns only string (i.e. not string[])
- dist/admin.php: HTTP POST panel
- LogMysqli::queryStrictObject Logs SQL statement not starting with SELECT or SET. *Throws exception in case response isn't `\mysqli_result`*
- LogMysqli::queryStrictBool Logs SQL statement not starting with SELECT or SET. *Throws exception in case response isn't `true`*
- type hints (especially array iterable value types)
- CHANGELOG.md
  - .markdown-lint.yml (to ignore same headings in CHANGELOG) replaces the default one, hence SHOULD include the original settings
- '7.0', '7.2' added to PHPStan matrix
  - not 7.1 as due to <https://bugs.php.net/bug.php?id=73803> ZipArchive class has public properties, that are not visible via reflection.
  - Therefore using tools like PHPStan generates error: Access to an undefined property ZipArchive::$numFiles. in class\MyAdminProcess
- dist phpstan includes phpstan/phpstan-webmozart-assert, therefore other PHPStan configuration file added in order to include proper extension.neon
- If DEBUG_VERBOSE is true and admin UI uses untranslated string, it is logged to `log/translate_admin_missing.log` to be translated
- dist/TableAdmin templates for methods customInput and customInputAfter added
- dist/Admin outputSpecialMenuLinks and projectSpecificSections etc overrides of MyAdmin methods added
- featureFlags work also in Admin UI
- Admin.php, AdminProcess.php, admin.js and admin.css now contains (almost) all the code from A and F projects - some is however not working (hence featureFlag 'order_hierarchy') TODO: simplify it and keep only the essential
- Admin UI: Friendly URL: one place to set them all, identify duplicities
- Admin UI: generate translations. Note: this rewrites the translation files language-xx.inc.php

### Changed
- nette/utils allowed also in version ^3.2.2
- godsdev/tools bumped to type strict version ^0.3.8
- LogMysqli::fetchSingle Throws excepetion, when an SQL statement returns true.  
- LogMysqli::fetchAndReindex Error for this function is also an SQL statement that returns true.
- MyAdminProcess::redir make use of new option in GodsDev\Tools::redir and turns off  `session_write_close()` in order to pass info about redirect to Tracy
- MyCMSMonoLingual if logger is not passed to the class, constructor will throw an Exception (instead of die)
- MyTableAdmin::outputField fixesParameter 2 $label of static method GodsDev\Tools\Tools::htmlInput() expects string, false given. MUST be empty string to trigger label omitting.
- MyTableAdmin methods (recordDelete) refactoring for better readability
- MyTableLister::resolveSQL refactoring for better readability
- ProjectCommon::localDate throws Exception if $stringOfTime is malformed
- dist/AdminProcess::getAgenda refactoring for better static analysis
- dist/Controller instatiates Mail only if `class_exists('Swift_SmtpTransport')` so that PHPUnit test run from root doesn't stupidly fail
- dist/ProjectSpecific::getSitemap throws Exception if sitemap retrieval fails
- `return;` statement after method with return never is not necessary
- MyTableAdmin: removed zero value that is not accepted by ENUM. To set empty, use NULL option. TODO fix NULL option for ENUM to be saved in database
- MyFriendlyURL::friendlyIdentifyRedirect throws Exception on seriously malformed URL
- dist/TableAdmin loads localised strings from conf/l10n/admin-XX.yml
- use only phinx.yml environment for database connection (instead of duplicating it in config.local.php)
- **potential breaking change**: MyTableLister::contentByType ignores missing $options['return-output'] and always return string, never echo string
- Bootstrap v4.0.0-beta -> Bootstrap v4.1.3, which is the highest version that works properly with Summernote 0.8.18 (background under buttons, but Full screen doesn't use background)
- jQuery v3.2.1 -> jQuery v3.6.0
- proper attribution: copyright of Admin UI to WorkOfStan & CRS2 (instead of GODS)

### Fixed
- Stricter code by type assertion, type casting, type hinting
- Stricter code by return type specific LogMysqli::queryStrict methods and ThrowablePHPFunctions (i.e. Exception thrown instead of an unexpected type returned on error)
- PHPStan level=6 => Error Zero
- MyAdminProcess::processSubFolder fixed EXIF related condition so that if nothing is known $entry['info'] .= '';
- (maybe the bug wasn't present in 0.3.15) MyAdmin: fix tableAdmin prevails TableAdmin
- when adding new content, automatically uncheck the NULL checkbox + save NULL value of checkbox
- Admin::searchColumns searches within existing columns

### Deprecated
- MyTableAdmin::outputSelectPath() - is this function necessary?

... and many more between 2020-05-02 and 2021-04-17 (TODO to be added)

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
- změny v dist/* aby se lépe nasazovaly nové projekty, např. dist/build.sh as a fast deployment script
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
  - cusomized Css and Js
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

## TODO - Add Types of changes
- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities.



[Unreleased]: https://github.com/WorkOfStan/mycms/compare/v0.3.15...HEAD
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

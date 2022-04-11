#!/bin/bash

echo "** initialize the vendor folder, if needed"
composer install -a --prefer-dist --no-progress

echo "** require --dev phpstan"
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress --with-all-dependencies

echo "** phpunit"
vendor/bin/phpunit

echo "** phpstan"
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

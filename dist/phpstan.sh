#!/bin/bash

# color constants
HIGHLIGHT='\033[1;36m' # light cyan
NC='\033[0m' # No Color

printf "${HIGHLIGHT}* initialize the vendor folder, if needed${NC}\n"
composer install -a --prefer-dist --no-progress

printf "${HIGHLIGHT}* require --dev phpstan${NC}\n"
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress --with-all-dependencies

printf "${HIGHLIGHT}* phpunit${NC}\n"
vendor/bin/phpunit

printf "${HIGHLIGHT}* phpstan${NC}\n"
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

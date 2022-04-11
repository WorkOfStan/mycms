#!/bin/bash

# color constants
HIGHLIGHT='\033[1;36m' # light cyan
NC='\033[0m' # No Color

printf "${HIGHLIGHT}* initialize the vendor folder, if needed${NC}\n"
composer install -a --prefer-dist --no-progress

#composer require --dev phpstan/phpstan-phpunit --prefer-dist --no-progress
# Note: rector/rector:0.11.60 => phpstan/phpstan:0.12.99 (i.e. prevents phpstan/phpstan:1.0.2)
#composer require --dev rector/rector --prefer-dist --no-progress

printf "${HIGHLIGHT}* require --dev phpstan${NC}\n"
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress

printf "${HIGHLIGHT}* phpunit${NC}\n"
vendor/bin/phpunit

printf "${HIGHLIGHT}* phpstan${NC}\n"
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

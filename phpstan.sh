#!/bin/bash

# output string param1 with color highlighting
section_title() {
    # color constants
    #HIGHLIGHT='\033[1;36m' # light cyan
    #NC='\033[0m' # No Color
    printf "\033[1;36m%s\033[0m\n" "$1"
}

section_title "* initialize the vendor folder, if needed"
composer install -a --prefer-dist --no-progress

#composer require --dev phpstan/phpstan-phpunit --prefer-dist --no-progress
# Note: rector/rector:0.11.60 => phpstan/phpstan:0.12.99 (i.e. prevents phpstan/phpstan:1.0.2)
#composer require --dev rector/rector --prefer-dist --no-progress

section_title "* require --dev phpstan"
# the next line can't be used because since phpstan/phpstan:1.7.0, PHPStan returns a lot of false positives
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress --with-all-dependencies
# until the issue is solved, let's limit PHPStan version
#composer require --dev phpstan/phpstan:1.6.9 --prefer-dist --no-progress --with-all-dependencies
#composer require --dev phpstan/phpstan-webmozart-assert:1.1.2 --prefer-dist --no-progress --with-all-dependencies

section_title "* phpunit"
vendor/bin/phpunit

section_title "* phpstan"
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

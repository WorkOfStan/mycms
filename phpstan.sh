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

#TODO once PHPUnit>=7 will only be used
#composer require --dev phpstan/phpstan-phpunit --prefer-dist --no-progress

section_title "* require --dev phpstan"
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress --with-all-dependencies

section_title "* phpunit"
vendor/bin/phpunit

section_title "* phpstan"
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

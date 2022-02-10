#!/bin/bash

# initialize the vendor folder, if needed
composer install -a --prefer-dist --no-progress

#composer require --dev phpstan/phpstan-phpunit --prefer-dist --no-progress
# Note: rector/rector:0.11.60 => phpstan/phpstan:0.12.99 (i.e. prevents phpstan/phpstan:1.0.2)
#composer require --dev rector/rector --prefer-dist --no-progress
#composer require --dev phpstan/phpstan-webmozart-assert:^0.12 --prefer-dist --no-progress
composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress

vendor/bin/phpunit
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

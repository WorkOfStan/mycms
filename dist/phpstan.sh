#!/bin/bash

composer require --dev phpstan/phpstan-webmozart-assert --prefer-dist --no-progress

vendor/bin/phpunit
vendor/bin/phpstan.phar --configuration=conf/phpstan.webmozart-assert.neon analyse . --memory-limit 300M --pro

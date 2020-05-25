#!/bin/bash

composer update -a
vendor/bin/phinx migrate -e development
vendor/bin/phpunit
sass styles/index.sass styles/index.css

#!/bin/bash

composer update
vendor/bin/phinx migrate -e development
vendor/bin/phpunit

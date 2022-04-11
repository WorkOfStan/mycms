#!/bin/bash

# output string param1 with color highlighting
section_title() {
    # color constants
    #HIGHLIGHT='\033[1;36m' # light cyan
    #NC='\033[0m' # No Color
    printf "\033[1;36m%s\033[0m\n" "$1"
}

echo "To work on low performing environments, the script accepts number of seconds as parameter to be used as a waiting time between steps."
paramSleepSec=0
[ "$1" ] && [ "$1" -ge 0 ] && paramSleepSec=$1

if [[ ! -f "conf/config.local.dist.php" || ! -f "phinx.yml" ]]; then
    [ ! -f "conf/config.local.php" ] && cp -p conf/config.local.dist.php conf/config.local.php && echo "Check the newly created conf/config.local.php"
    [ ! -f "phinx.yml" ] && cp -p phinx.dist.yml phinx.yml && echo "Check the newly created phinx.yml"
    exit 0
fi

section_title "* composer update"
composer update -a --prefer-dist --no-progress
sleep "$paramSleepSec"s

section_title "* phinx"
vendor/bin/phinx migrate -e development
sleep "$paramSleepSec"s

section_title "* phinx testing"
# In order to properly unit test all features, set-up a test database, put its credentials to testing section of phinx.yml and run phinx migration -e testing before phpunit
# Drop tables in the testing database if changes were made to migrations
vendor/bin/phinx migrate -e testing
sleep "$paramSleepSec"s

section_title "* phpunit"
vendor/bin/phpunit
sleep "$paramSleepSec"s

section_title "* sass"
sass styles/index.sass styles/index.css

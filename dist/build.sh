#!/bin/bash

echo "To work on low performing environments, the script accepts number of seconds as parameter to be used as a waiting time between steps."
paramSleepSec=0
[ "$1" ] && [ "$1" -ge 0 ] && paramSleepSec=$1

composer update -a
sleep "$paramSleepSec"s
vendor/bin/phinx migrate -e development
sleep "$paramSleepSec"s
vendor/bin/phpunit
sleep "$paramSleepSec"s
sass styles/index.sass styles/index.css

#!/bin/bash
# (Last MyCMS/dist revision: 2023-06-16, v0.4.9)

# output string param1 with color highlighting
section_title() {
	# color constants
	#HIGHLIGHT='\033[1;36m' # light cyan
	#NC='\033[0m' # No Color
	printf "\033[1;36m%s\033[0m\n" "$1"
}

warning() {
	# color constants
	#WARNING='\033[0;31m' # red
	#NC='\033[0m' # No Color
	printf "\033[0;31m%s\033[0m\n" "$1"
}

echo "To work on low performing environments, the script accepts number of seconds as parameter to be used as a waiting time between steps."
paramSleepSec=0
[ "$1" ] && [ "$1" -ge 0 ] && paramSleepSec=$1

# Create config.local.php if not present but the dist template is available, if newly created stop the script so that the admin may adapt the newly created config
[[ ! -f "conf/config.local.php" && -f "conf/config.local.dist.php" ]] && cp -p conf/config.local.dist.php conf/config.local.php && warning "Check/modify the newly created conf/config.local.php" && exit 0

# phinx.yml or at least phinx.dist.yml is required
if [[ ! -f "phinx.yml" ]]; then
	[[ ! -f "phinx.dist.yml" ]] && warning "phinx config is required for a MyCMS app" && exit 0
	cp -p phinx.dist.yml phinx.yml && warning "Check/modify the newly created phinx.yml"
	exit 0
fi

section_title "* composer update"
composer update -a --prefer-dist --no-progress
sleep "$paramSleepSec"s

section_title "* phinx"
vendor/bin/phinx migrate -e development
sleep "$paramSleepSec"s

section_title "* phinx testing"
# In order to properly unit test all features, set-up a test database, put its credentials to testing section of phinx.yml and run phinx migrate -e testing before phpunit
# Drop tables in the testing database if changes were made to migrations
vendor/bin/phinx migrate -e testing
sleep "$paramSleepSec"s

[ ! -f "phpunit.xml" ] && warning "NO phpunit.xml CONFIGURATION"
if [[ -f "phpunit.xml" ]]; then
	section_title "* phpunit"
	vendor/bin/phpunit
	sleep "$paramSleepSec"s
fi

section_title "* sass"
sass styles/index.sass styles/index.css

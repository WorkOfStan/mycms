#!/usr/bin/env bash

# Note: build.sh is used to create if !exists phinx.yml & conf/config.local.php

# directories where www-data MUST have permission to write
chmod g+w cache
chmod g+w log
#chmod g+w api/*/log

# legacy files in those directories MUST have the right permissions in order to be writable (No legacy files: chmod: cannot access 'cache/*' ...)
chmod 660 cache/*
chmod 660 log/*
#chmod 660 api/*/log/*

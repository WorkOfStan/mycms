<?php

/**
 * Predefining constants for PHPSTAN analysis as recommanded by
 * https://phpstan.org/user-guide/discovering-symbols
 *
 * add `define('PREDEFINED_CONSTANT', 'value');` as needed on top of conf/config.php and conf/config.local.dist.php
 *
 */

// Database related constants
define('DB_DATABASE', 'MYCMSPROJECTSPECIFIC');
define('DB_HOST', 'localhost');
define('DB_PASSWORD', 'password');
define('DB_PORT', ini_get('mysqli.default_port'));
define('TAB_PREFIX', 'MYCMSPROJECTSPECIFIC_'); //prefix for database tables
define('DB_USERNAME', 'username');

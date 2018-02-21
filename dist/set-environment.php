<?php

/**
 * setting the environment may be different for each MyCMS deployment
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();
ini_set('session.http_only', true);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cokie_lifetime', 2000000);
session_set_cookie_params(10800, '/');
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once './conf/env_config.php';

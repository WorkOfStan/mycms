<?php

/**
 * setting the environment may be different for each MyCMS deployment
 * (Last MyCMS/dist revision: 2022-02-04, v0.4.5)
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('session.http_only', '1');
if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
    ini_set('session.cookie_secure', '1');
}
ini_set('session.cookie_httponly', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '200000');
ini_set('session.cokie_lifetime', '2000000');
session_set_cookie_params(10800, '/');
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once __DIR__ . '/config.php';

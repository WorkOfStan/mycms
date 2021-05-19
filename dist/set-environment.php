<?php

/**
 * setting the environment may be different for each MyCMS deployment
 */

error_reporting(E_ALL & ~E_NOTICE);
// TODO fix 3 lines below: '#Parameter \#2 \$newvalue of function ini_set expects string, true given.#'
ini_set('session.http_only', true); // @phpstan-ignore-line TODO true as string?
if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
    ini_set('session.cookie_secure', true); // @phpstan-ignore-line TODO true as string?
}
ini_set('session.cookie_httponly', true); // @phpstan-ignore-line TODO true as string?
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '200000');
ini_set('session.cokie_lifetime', '2000000');
session_set_cookie_params(10800, '/');
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once __DIR__ . '/conf/config.php';

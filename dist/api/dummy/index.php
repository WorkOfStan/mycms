<?php

/**
 * Dummy API encoded below that co-exists with Controller served APIs
 *
 */

use WorkOfStan\mycmsprojectnamespace\Utils;

require './../../set-environment.php';

// Under construction section
// Note: if condition changes, pls change also $developmentEnvironment assignement in prepare.php
if (UNDER_CONSTRUCTION && !(in_array($_SERVER['REMOTE_ADDR'], $debugIpArray))) {
    include './../../under-construction.html';
    exit;
}

require_once './../../prepare.php';

$directJsonCall = Utils::directJsonCall($_SERVER['HTTP_ACCEPT']);

Utils::jsonOrEcho(["dummy" => "Hello world!"], $directJsonCall, $backyard);

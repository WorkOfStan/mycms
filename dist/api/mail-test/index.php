<?php
//Mail sample service api:
//Get content "latest_test_email_sent" timestamp. If not set, init and return "not sending, init"
//If set, <24h return "cooling-off period running"
//Update content "latest_test_email_sent" timestamp.
//Send to adminMail a Mail with php version and MyCMS version test; return result

//Try on 7 and also 5.6

require './../../set-environment.php'; 
// Under construction section
// Note: if condition changes, pls change also $developmentEnvironment assignement in prepare.php
if (     UNDER_CONSTRUCTION && !(     // line below to be used only if behind firewall and the original REMOTE_ADDR present in HTTP_CLIENT_IP     //  - otherwise it should not be used as it would be a vulnerability     //isset($_SERVER['HTTP_CLIENT_IP']) ? in_array($_SERVER['HTTP_CLIENT_IP'], $debugIpArray) :     in_array($_SERVER['REMOTE_ADDR'], $debugIpArray)  )  

) {     include './../../under-construction.html';     exit;
} 
require_once './../../prepare.php'; 
use Tracy\Debugger; 
Debugger::enable(Debugger::PRODUCTION, DIR_TEMPLATE . '/../log');  
$backyard->Json->outputJSON('{"action":"3-' . $_SESSION['language'] . '"}', true); 

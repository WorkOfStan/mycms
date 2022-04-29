<?php

$file = __DIR__ . '/../vendor/workofstan/mycms/styles/admin.css';
if (is_file($file)) {
    header("Content-type: text/css", true);
    header("Content-Length: " . filesize($file));
    readfile($file);
}
exit(); // to prevent the IE Mime-type sniffing

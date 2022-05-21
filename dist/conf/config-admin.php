<?php

/**
 * Admin UI config
 * (Last MyCMS/dist revision: 2022-05-21, v0.4.6+)
 */

//$AGENDAS is used in AdminProcess.php. If $_SESSION['language'] is used in it, set it after prepare.php,
//where $_SESSION['language'] is fixed. For reference see README.md.
$AGENDAS = [
    'category' => [
        'column' => "name_{$_SESSION['language']}",
        'prefill' => [
            'sort' => 0,
            'added' => 'now',
        ]
    ],
    'product' => [
        'column' => "name_{$_SESSION['language']}",
        'prefill' => [
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'article' => [
        'table' => 'content',
        'where' => 'type="article"',
        'column' => ['code', "name_{$_SESSION['language']}"],
        'prefill' => [
            'type' => 'article',
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'ad' => [
        'table' => 'content',
        'where' => 'type="ad"',
        'column' => ['code', "name_{$_SESSION['language']}"],
        'prefill' => [
            'type' => 'ad',
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'redirector' => [
        'table' => 'redirector',
        'column' => 'old_url',
        'prefill' => [
            'added' => 'now',
        ],
    ],
];

// a present get parameter toggles active tab - expected in admin.php
$getSwitch = ['divisions-products', 'pages', 'products', 'translations', 'urls'];

if (file_exists(__DIR__ . '/config-admin.local.php')) {
    include_once __DIR__ . '/config-admin.local.php'; // use config-admin.local.dist.php as specimen
}

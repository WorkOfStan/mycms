<?php

/**
 * Admin UI config
 * (Last MyCMS/dist revision: 2022-05-21, v0.4.7)
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

$myCmsConfAdmin = [
    // a present get parameter toggles active tab - expected in admin.php
    // => template assignement (only first fits is taken into account)
    'get2template' => [
        'divisions-products' => 'Admin/divisions-products',
        'pages' => 'Admin/pages',
        'products' => 'Admin/products',
        'translations' => 'Admin/translations',
        'urls' => 'Admin/urls',
    ],
    // a present get parameter toggles active tab - expected in admin.php
    // => Name to translate
//    'tabs' => [
//        'divisions-products' => 'Divisions and products',
//        'pages' => 'Pages',
//        'products' => 'Products',
//        'translations' => 'Translations',
//        'urls' => 'URL',
//    ],
    // array<array<string>> tables and columns to search in admin
    // table => [id, field1 to be searched in, field2 to be searched in...]
    'searchColumns' => [
        'category' => ['id', 'name_#', 'content_#'], // "#" will be replaced by current language
        'content' => ['id', 'name_#', 'content_#'], // "#" will be replaced by current language
        'product' => ['id', 'name_#', 'content_#'], // "#" will be replaced by current language
    ],
];

if (file_exists(__DIR__ . '/config-admin.local.php')) {
    include_once __DIR__ . '/config-admin.local.php'; // use config-admin.local.dist.php as specimen
}

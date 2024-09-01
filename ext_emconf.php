<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'URL shortener',
    'description' => 'This extensions allows you to cut down long URLs. It basically works like bitly or TinyURL.',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Alexander Stehlik',
    'author_email' => 'alexander.stehlik.deleteme@gmail.com',
    'author_company' => '',
    'version' => '13.2.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.3.99',
            'typo3' => '13.1.0-13.1.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

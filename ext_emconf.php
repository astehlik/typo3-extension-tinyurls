<?php
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
    'version' => '2.1.0-dev',
    'constraints' => [
        'depends' => [
            'php' => '7.1.0-7.3.99',
            'typo3' => '8.7.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

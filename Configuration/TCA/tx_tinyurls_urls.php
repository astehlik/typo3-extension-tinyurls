<?php

declare(strict_types=1);

use Tx\Tinyurls\FormEngine\TinyUrlDisplay;

$languagePrefix = 'LLL:EXT:tinyurls/Resources/Private/Language/locallang_db.xlf:tx_tinyurls_urls.';

return [
    'ctrl' => [
        'title' => 'Tiny URL',
        'label' => 'target_url',
        'tstamp' => 'tstamp',
        'default_sortby' => 'ORDER BY target_url',
        'enablecolumns' => ['endtime' => 'valid_until'],
        'iconfile' => 'EXT:tinyurls/ext_icon.gif',
        'searchFields' => 'urlkey,target_url,target_url_hash',
        'rootLevel' => -1,
    ],
    'columns' => [
        'counter' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'counter',
            'config' => [
                'type' => 'input',
                'size' => 6,
                'readOnly' => 1,
            ],
        ],
        'comment' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'comment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ],
        ],
        'urlkey' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'url_key',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => 1,
            ],
        ],
        'urldisplay' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'tiny_url',
            'config' => [
                'type' => 'tx_tinyurls_copyable_field',
                /** @uses TinyUrlDisplay::buildTinyUrlFormFormElementData() */
                'valueFunc' => TinyUrlDisplay::class . '->buildTinyUrlFormFormElementData',
                'size' => 30,
            ],
        ],
        'target_url' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'target_url',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'required' => true,
                'eval' => 'trim,nospace,unique',
            ],
        ],
        'target_url_hash' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'target_url_hash',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
                'readOnly' => 1,
            ],
        ],
        'delete_on_use' => [
            'exclude' => 0,
            'label' => $languagePrefix . 'delete_on_use',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'valid_until' => [
            'label' => $languagePrefix . 'valid_until',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'urldisplay,counter,target_url,target_url_hash,comment,delete_on_use,valid_until'],
    ],
    'palettes' => [],
];

<?php
$languagePrefix = 'LLL:EXT:tinyurls/Resources/Private/Language/locallang_db.xlf:tx_tinyurls_urls.';

return [
    'ctrl' => [
        'title' => 'Tiny URL',
        'label' => 'target_url',
        'tstamp' => 'tstamp',
        'default_sortby' => 'ORDER BY target_url',
        'enablecolumns' => ['endtime' => 'valid_until'],
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tinyurls') . 'ext_icon.gif',
        'searchFields' => 'urlkey,target_url,target_url_hash',
        'rootLevel' => -1,
    ],
    'interface' => [
        'showRecordFieldList' => 'urlkey,target_url,target_url_hash,delete_on_use,valid_until',
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
            'label' => 'Comment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ],
        ],
        'urlkey' => [
            'exclude' => 0,
            'label' => 'URL key',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => 1,
            ],
        ],
        'urldisplay' => [
            'exclude' => 0,
            'label' => 'Tiny URL',
            'config' => [
                'type' => 'tx_tinyurls_copyable_field',
                'valueFunc' => \Tx\Tinyurls\FormEngine\TinyUrlDisplay::class . '->buildTinyUrlFormFormElementData',
                'size' => 30,
            ],
        ],
        'target_url' => [
            'exclude' => 0,
            'label' => 'Target URL',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'required,trim,nospace,unique',
            ],
        ],
        'target_url_hash' => [
            'exclude' => 0,
            'label' => 'Target URL Hash',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required',
                'readOnly' => 1,
            ],
        ],
        'delete_on_use' => [
            'exclude' => 0,
            'label' => 'Delete on use',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'valid_until' => [
            'label' => 'Valid until',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 20,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'urldisplay,counter,target_url,target_url_hash,comment,delete_on_use,valid_until',
        ],
    ],
    'palettes' => [],
];

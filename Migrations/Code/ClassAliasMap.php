<?php

declare(strict_types=1);

return [
    'tx_tinyurls_hooks_eidprocessor' => 'Tx\\Tinyurls\\Hooks\\EidProcessor',
    'tx_tinyurls_hooks_tce' => 'Tx\\Tinyurls\\Hooks\\TceDataMap',
    'tx_tinyurls_hooks_typolink' => 'Tx\\Tinyurls\\Hooks\\TypoLink',
    'tx_tinyurls_tinyurl_api' => 'Tx\\Tinyurls\\TinyUrl\\Api',
    'tx_tinyurls_tinyurl_tinyurlgenerator' => 'Tx\\Tinyurls\\TinyUrl\\TinyUrlGenerator',
    'tx_tinyurls_utils_configutils' => 'Tx\\Tinyurls\\Utils\\ConfigUtils',
    'tx_tinyurls_utils_urlutils' => 'Tx\\Tinyurls\\Utils\\UrlUtils',

    'Tx\\Tinyurls\\Hooks\\EidProcessor' => 'Tx\\Tinyurls\\Controller\\EidController',
    'Tx\\Tinyurls\\Utils\\ConfigUtils' => 'Tx\\Tinyurls\\Configuration\\ExtensionConfiguration',
];

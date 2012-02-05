<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/EidProcessor.php';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_tinyurls'] = 'tx_tinyurls_hooks_typolink->createTinyUrl';

?>
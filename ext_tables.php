<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_tinyurls_urls'] = array(
	'ctrl' => array(
		'title' => 'Tiny URL',
		'label' => 'target_url',
		'tstamp' => 'tstamp',
		'default_sortby' => 'ORDER BY target_url',
		'enablecolumns' => array(
			'endtime' => 'valid_until',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca.php',
		'searchFields' => 'urlkey,target_url,target_url_hash',
		'rootLevel' => 1,
	),
);
<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_tinyurls_urls'] = array(
	'ctrl' => $TCA['tx_tinyurls_urls']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'urlkey,target_url,target_url_hash,delete_on_use,valid_until'
	),
	'feInterface' => $TCA['tx_tinyurls_urls']['interface'],
	'columns' => array(
		'urlkey' => array(
			'exclude' => 0,
			'label' => 'URL key',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'readOnly' => 1,
			)
		),
		'target_url' => array(
			'exclude' => 0,
			'label' => 'Target URL',
			'config' => array(
				'type' => 'input',
				'size' => 50,
				'eval' => 'required,trim,nospace,unique',
			),
		),
		'target_url_hash' => array(
			'exclude' => 0,
			'label' => 'Target URL Hash',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'required',
				'readOnly' => 1,
			)
		),
		'delete_on_use' => array(
			'exclude' => 0,
			'label' => 'Delete on use',
			'config'  => array(
				'type'    => 'check',
				'default' => 0
			)
		),
		'valid_until' => array(
			'label'   => 'Valid until',
			'config'  => array(
				'type'     => 'input',
				'size'     => 10,
				'max'      => 20,
				'eval'     => 'datetime',
				'default'  => 0,
			)
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => 'urlkey,target_url,target_url_hash,delete_on_use,valid_until'
		),
	),
	'palettes' => array(),
);
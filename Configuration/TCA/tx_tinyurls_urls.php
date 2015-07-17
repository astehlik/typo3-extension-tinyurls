<?php
return array(
	'ctrl' => array(
		'title' => 'Tiny URL',
		'label' => 'target_url',
		'tstamp' => 'tstamp',
		'default_sortby' => 'ORDER BY target_url',
		'enablecolumns' => array(
			'endtime' => 'valid_until',
		),
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tinyurls') . 'ext_icon.gif',
		'searchFields' => 'urlkey,target_url,target_url_hash',
		'rootLevel' => -1,
	),
	'interface' => array(
		'showRecordFieldList' => 'urlkey,target_url,target_url_hash,delete_on_use,valid_until'
	),
	'columns' => array(
		'counter' => array(
			'exclude' => 0,
			'label' => 'Counter',
			'config' => array(
				'type' => 'input',
				'size' => 6,
				'readOnly' => 1,
			)
		),
		'comment' => array(
			'exclude' => 0,
			'label' => 'Comment',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
			)
		),
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
			'config' => array(
				'type' => 'check',
				'default' => 0
			)
		),
		'valid_until' => array(
			'label' => 'Valid until',
			'config' => array(
				'type' => 'input',
				'size' => 10,
				'max' => 20,
				'eval' => 'datetime',
				'default' => 0,
			)
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => 'urlkey,counter,target_url,target_url_hash,comment,delete_on_use,valid_until'
		),
	),
	'palettes' => array(),
);
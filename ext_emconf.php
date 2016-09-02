<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'URL shortener',
	'description' => 'This extensions allows you to cut down long URLs. It basically works like bitly or TinyURL.',
	'category' => 'plugin',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Alexander Stehlik',
	'author_email' => 'alexander.stehlik.deleteme@gmail.com',
	'author_company' => '',
	'version' => '2.0.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.5.0-0.0.0',
			'typo3' => '6.2.3-7.6.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
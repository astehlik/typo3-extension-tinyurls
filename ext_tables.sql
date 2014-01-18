#
# Table structure for table 'tx_tinyurls_urls'
#
CREATE TABLE tx_tinyurls_urls (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	counter int(11) unsigned DEFAULT '0' NOT NULL,
	comment text,
	urlkey varchar(255) DEFAULT '' NOT NULL,
	target_url text,
	target_url_hash varchar(40) DEFAULT '' NOT NULL,
	delete_on_use tinyint(4) unsigned DEFAULT '0' NOT NULL,
	valid_until int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY parent (target_url_hash),
	UNIQUE KEY tinyurl (urlkey)
);

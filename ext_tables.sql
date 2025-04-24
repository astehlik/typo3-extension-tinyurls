#
# Table structure for table 'tx_tinyurls_urls'
#
CREATE TABLE tx_tinyurls_urls (
	counter int(11) unsigned DEFAULT '0' NOT NULL,
	comment text,
	urlkey varchar(255) DEFAULT '' NOT NULL,
	urldisplay varchar(1) DEFAULT '' NOT NULL,
	target_url text,
	target_url_hash varchar(40) DEFAULT '' NOT NULL,
	delete_on_use tinyint(4) unsigned DEFAULT '0' NOT NULL,
	valid_until int(11) unsigned DEFAULT '0' NOT NULL,
	KEY target_url_hash (pid, target_url_hash),
	KEY valid_until (valid_until ASC),
	UNIQUE KEY tinyurl (pid, urlkey)
);

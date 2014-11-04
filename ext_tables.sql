
#
# Table for mkforms session cache
#
CREATE TABLE cf_mkforms (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	content longblob NOT NULL,
	expires int(11) DEFAULT '0' NOT NULL,
	lifetime int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (id),
	KEY cache_id (identifier,expires)
);

#
# Unused dummy table for TYPO3 caching framework
#
CREATE TABLE cf_mkforms_tags (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	tag varchar(250) DEFAULT '' NOT NULL,

	PRIMARY KEY (id),
	KEY cache_id (identifier),
	KEY cache_tag (tag)
);

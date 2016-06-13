<?php

########################################################################
# Extension Manager/Repository config file for ext: "mkforms"
#
# Auto generated 09-03-2008 22:19
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MK Forms',
	'description' => 'Making HTML forms for TYPO3',
	'category' => 'misc',
	'shy' => 0,
	'version' => '2.0.5',
	'dependencies' => '',
	'conflicts' => 'ameos_formidable',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/mkforms/cache/',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'René Nitzsche,Michael Wagner,Hannes Bochmann',
	'author_email' => 'dev@dmk-business.de',
	'author_company' => 'DMK E-BUSINESS GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'rn_base' => '1.0.9-',
			'typo3' => '4.5.0-7.6.99',
		),
		'conflicts' => array(
			'ameos_formidable' => ''
		),
		'suggests' => array(
			'mkmailer' => '0.7.6-',
			'mklib' => '0.9.84-',
		),
	),
	'_md5_values_when_last_written' => '',
);


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
	'version' => '0.22.21',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/mkforms/cache/',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'René Nitzsche',
	'author_email' => 'nitzsche@das-medienkombinat.de',
	'author_company' => 'das MedienKombinat GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'rn_base' => '',
		),
		'conflicts' => array(
			'ameos_formidable'
		),
		'suggests' => array(
			'mkmailer' => '0.7.6-',
		),
	),
	'_md5_values_when_last_written' => '',
);

?>

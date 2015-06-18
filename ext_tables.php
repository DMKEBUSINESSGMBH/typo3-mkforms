<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

////////////////////////////////
// Plugin anmelden
////////////////////////////////
// Einige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_mkforms'] = 'layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_mkforms'] = 'pi_flexform';

t3lib_extMgm::addPiFlexFormValue('tx_mkforms', 'FILE:EXT:' . $_EXTKEY . '/flexform_main.xml');

t3lib_extMgm::addPlugin(
	array(
		'LLL:EXT:' . $_EXTKEY . '/locallang_db.php:plugin.mkforms.label',
		'tx_mkforms',
		t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif',
	)
);

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/ts/', 'MKFORMS - Basics');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/prototype/', 'MKFORMS Prototype-JS');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/jquery/', 'MKFORMS JQuery-JS');

?>

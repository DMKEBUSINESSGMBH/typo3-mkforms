<?php

if (!defined ('TYPO3_MODE'))     die ('Access denied.');

////////////////////////////////
// Plugin anmelden
////////////////////////////////
// Einige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_mkforms']='layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_mkforms'] = 'pi_flexform';

t3lib_extMgm::addPiFlexFormValue('tx_mkforms','FILE:EXT:'.$_EXTKEY.'/flexform_main.xml');

//t3lib_extMgm::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.mkforms.label','tx_mkforms'));
t3lib_extMgm::addPlugin(
		array(
			'LLL:EXT:'.$_EXTKEY.'/locallang_db.php:plugin.mkforms.label',
			'tx_mkforms',
			t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif',
		)
	);

t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts/', 'MKFORMS - Basics');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/prototype/', 'MKFORMS Prototype-JS');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/jquery/', 'MKFORMS JQuery-JS');

//	t3lib_div::loadTCA('tt_content');
//	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
//	t3lib_extMgm::addPlugin(Array('FORMIDABLE cObj (cached)', $_EXTKEY.'_pi1'),'list_type');
//
//
//	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
//	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:' . $_EXTKEY . '/pi1/flexform.xml');
//
//	if (TYPO3_MODE=='BE') {
//		$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['ameos_formidable_pi1_wizicon'] =
//			t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_ameosformidable_pi1_wizicon.php';
//	}
//
//
//
//	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';
//	t3lib_extMgm::addPlugin(Array('FORMIDABLE_INT cObj (not cached)', $_EXTKEY.'_pi2'),'list_type');
//
//
//	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2']='pi_flexform';
//	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:' . $_EXTKEY . '/pi2/flexform.xml');

//	if (TYPO3_MODE=='BE') {
//		$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['ameos_formidable_pi2_wizicon'] =
//			t3lib_extMgm::extPath($_EXTKEY).'pi2/class.tx_ameosformidable_pi2_wizicon.php';
//	}

?>

<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

////////////////////////////////
// Plugin anmelden
////////////////////////////////
// Einige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_mkforms'] = 'layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_mkforms'] = 'pi_flexform';

tx_rnbase_util_Extensions::addPiFlexFormValue('tx_mkforms', 'FILE:EXT:' . $_EXTKEY . '/flexform_main.xml');

tx_rnbase_util_Extensions::addPlugin(
    array(
        'LLL:EXT:' . $_EXTKEY . '/locallang_db.php:plugin.mkforms.label',
        'tx_mkforms',
        tx_rnbase_util_Extensions::extRelPath($_EXTKEY) . 'ext_icon.gif',
    )
);

tx_rnbase_util_Extensions::addStaticFile($_EXTKEY, 'static/ts/', 'MKFORMS - Basics');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY, 'static/prototype/', 'MKFORMS Prototype-JS');
tx_rnbase_util_Extensions::addStaticFile($_EXTKEY, 'static/jquery/', 'MKFORMS JQuery-JS');

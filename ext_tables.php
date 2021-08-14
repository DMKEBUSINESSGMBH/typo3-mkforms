<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

////////////////////////////////
// Plugin anmelden
////////////////////////////////
// Einige Felder ausblenden
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_mkforms'] = 'layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_mkforms'] = 'pi_flexform';

tx_rnbase_util_Extensions::addPiFlexFormValue('tx_mkforms', 'FILE:EXT:mkforms/flexform_main.xml');

tx_rnbase_util_Extensions::addPlugin(
    [
        'LLL:EXT:mkforms/locallang_db.xml:plugin.mkforms.label',
        'tx_mkforms',
        'EXT:mkforms/ext_icon.gif',
    ],
    'list_type',
    'mkforms'
);

tx_rnbase_util_Extensions::addStaticFile('mkforms', 'static/ts/', 'MKFORMS - Basics');
tx_rnbase_util_Extensions::addStaticFile('mkforms', 'static/prototype/', 'MKFORMS Prototype-JS');
tx_rnbase_util_Extensions::addStaticFile('mkforms', 'static/jquery/', 'MKFORMS JQuery-JS');

<?php

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_mkforms'] = 'layout,select_key,pages';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_mkforms'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('tx_mkforms', 'FILE:EXT:mkforms/flexform_main.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:'.'mkforms'.'/Resources/Private/Language/locallang_db.xlf:plugin.mkforms.label',
        'tx_mkforms',
        'EXT:mkforms/Resources/Public/Icons/Extension.gif',
    ],
    'list_type',
    'mkforms'
);

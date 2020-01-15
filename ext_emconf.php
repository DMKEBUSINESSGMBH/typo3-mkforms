<?php

//
// Extension Manager/Repository config file for ext: 'mkforms'
//
// Auto generated 09-03-2008 22:19
//
// Manual updates:
// Only the data in the array - anything else is removed by next write.
// 'version' and 'dependencies' must not be touched!
//
$EM_CONF[$_EXTKEY] = [
    'title' => 'MK Forms',
    'description' => 'Making HTML forms for TYPO3',
    'category' => 'misc',
    'shy' => 0,
    'version' => '9.5.3',
    'dependencies' => '',
    'conflicts' => 'ameos_formidable',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'typo3temp/mkforms/cache/,typo3temp/assets/mkforms/',
    'modify_tables' => '',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'René Nitzsche,Michael Wagner,Hannes Bochmann',
    'author_email' => 'dev@dmk-business.de',
    'author_company' => 'DMK E-BUSINESS GmbH',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'rn_base' => '1.10.5-',
            'typo3' => '7.6.0-9.5.99',
            'php' => '5.6.0-8.9.99',
        ],
        'conflicts' => [
            'ameos_formidable' => '',
        ],
        'suggests' => [
            'mkmailer' => '3.0.0-',
            'mklib' => '3.0.0-',
            'mksanitizedparameters' => '3.0.0-',
        ],
    ],
    'autoload' => [
        'classmap' => [
            'action/',
            'api/',
            'Classes/',
            'dh/',
            'ds/',
            'exception/',
            'forms/',
            'hooks/',
            'js/',
            'remote/',
            'renderer/',
            'session/',
            'tests/',
            'util/',
            'validator/',
            'view/',
            'widgets/',
        ],
    ],
    '_md5_values_when_last_written' => '',
];

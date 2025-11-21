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
$EM_CONF['mkforms'] = [
    'title' => 'MK Forms',
    'description' => 'Making HTML forms for TYPO3',
    'category' => 'misc',
    'shy' => 0,
    'version' => '12.0.4',
    'dependencies' => '',
    'conflicts' => 'ameos_formidable',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
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
            'rn_base' => '1.17.0-',
            'typo3' => '11.5.7-12.4.99',
        ],
        'conflicts' => [
            'ameos_formidable' => '',
        ],
        'suggests' => [
            'mkmailer' => '12.0.0-',
            'mklib' => '12.0.0-',
            'mksanitizedparameters' => '12.0.0-',
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
            'js/',
            'remote/',
            'renderer/',
            'session/',
            'util/',
            'validator/',
            'view/',
            'widgets/',
        ],
    ],
    'autoload-dev' => [
        'classmap' => [
            'tests/',
        ],
    ],
];

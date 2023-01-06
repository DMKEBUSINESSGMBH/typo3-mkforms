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
    'version' => '10.1.4',
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
            'rn_base' => '1.15.0-',
            'typo3' => '9.5.0-10.4.99',
            'php' => '7.2.0-7.4.99',
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
    '_md5_values_when_last_written' => '',
];

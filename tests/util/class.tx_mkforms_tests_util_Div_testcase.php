<?php
/**
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <dev@dmk-business.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * benötigte Klassen einbinden
 */
tx_rnbase::load('tx_mkforms_util_Div');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 * Array util tests
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_util
 */
class tx_mkforms_tests_util_Div_testcase extends tx_rnbase_tests_BaseTestCase
{
    public function testToCamelCase()
    {
        self::assertEquals('FeUsers', tx_mkforms_util_Div::toCamelCase('fe_users'));
        self::assertEquals('feUsers', tx_mkforms_util_Div::toCamelCase('fe_users', '_', true));
        self::assertEquals('TxMklibWordlist', tx_mkforms_util_Div::toCamelCase('tx_mklib_wordlist'));
        self::assertEquals('TxMklibTestsUtilStringTestcase', tx_mkforms_util_Div::toCamelCase('tx_mklib_tests_util_String_testcase'));
    }
    public function testGetSetupByKeys()
    {
        $aConfig = [
                'lib.' => [
                    'mkforms.' => [
                        'formbase.' => [
                            'testmode' => 1,
                        ],
                    ],
                    'mkextension.' => [
                        'installed' => 1,
                    ],
                ],
                'plugin.' => [
                    'tx_mkforms' => 'USER_INT',
                    'tx_mkforms.' => [
                        'genericTemplate' => 'EXT:mkforms/templates/formonly.html',
                    ],
                    'tx_mkextension.' => [
                        'extensionTemplate' => 'EXT:mkextension/templates/template.html',
                    ],
                ],
                'config.' => [
                    'tx_mkforms.' => [
                        'cache.' => [
                            'tsPaths' => 0,
                        ],
                    ],
                ],
        ];
        $aWill = [
                'lib' => 1,
                'plugin.' => [
                    'tx_mkforms' => 1,
                ],
        ];
        $aArray = tx_mkforms_util_Div::getSetupByKeys($aConfig, $aWill);
        self::assertTrue(array_key_exists('lib.', $aArray), 'lib. not found in array.');
        self::assertTrue(array_key_exists('mkforms.', $aArray['lib.']), 'lib.mkforms. not found in array.');
        self::assertTrue(array_key_exists('formbase.', $aArray['lib.']['mkforms.']), 'lib.mkforms.formbase. not found in array.');
        self::assertTrue(array_key_exists('testmode', $aArray['lib.']['mkforms.']['formbase.']), 'lib.mkforms.formbase.testmode not found in array.');
        self::assertTrue((bool) $aArray['lib.']['mkforms.']['formbase.']['testmode'], 'lib.mkforms.formbase.testmode is not true.');
        self::assertTrue(array_key_exists('mkextension.', $aArray['lib.']), 'lib.mkextension. not found in array.');
        self::assertTrue(array_key_exists('installed', $aArray['lib.']['mkextension.']), 'lib.mkextension.installed not found in array.');
        self::assertTrue((bool) $aArray['lib.']['mkextension.']['installed'], 'lib.mkextension.installed is not true.');

        self::assertTrue(array_key_exists('plugin.', $aArray), 'plugin. not found in array.');
        self::assertTrue(array_key_exists('tx_mkforms', $aArray['plugin.']), 'plugin.tx_mkforms not found in array.');
        self::assertEquals('USER_INT', $aArray['plugin.']['tx_mkforms'], 'plugin.tx_mkforms not USER_INT.');
        self::assertTrue(array_key_exists('tx_mkforms.', $aArray['plugin.']), 'plugin.tx_mkforms. not found in array.');
        self::assertTrue(array_key_exists('genericTemplate', $aArray['plugin.']['tx_mkforms.']), 'plugin.tx_mkforms.genericTemplate not found in array.');
        self::assertEquals('EXT:mkforms/templates/formonly.html', $aArray['plugin.']['tx_mkforms.']['genericTemplate'], 'plugin.tx_mkforms.genericTemplate has wrong value.');

        self::assertFalse(array_key_exists('tx_mkextension.', $aArray['plugin.']), 'plugin.tx_mkextension. found in array.');
        self::assertFalse(array_key_exists('config.', $aArray), 'config. found in array.');
    }

    /**
     *
     * @param string $actual
     * @param string $expected
     * @param string $iconv
     *
     * @group unit
     * @dataProvider providerCleanupFileName
     */
    public function testCleanupFileName($rawFile, $expectedFile, $usesIconv = false)
    {
        if ($usesIconv && strpos(Tx_Rnbase_Utility_T3General::getHostname(), 'project.dmknet.de') === false) {
            // die Tests wurden direct für die locales konfig auf dmknet abgestimmt!
            $this->markTestSkipped(
                'Dieser Test kann wegen den locale' .
                ' Einstellungen nur auf project.dmknet.de ausgeführt werden.'
            );
        }

        $cleanedFile = tx_mkforms_util_Div::cleanupFileName($rawFile);
        self::assertEquals($expectedFile, $cleanedFile);
    }
    /**
     * DataProvider for cleanupFileName Test.
     *
     * @return array
     */
    public function providerCleanupFileName()
    {
        return [
            'line ' . __LINE__ => [
                'Süß_&_Snack.pdf',
                'suess___snack.pdf',
                true,
            ],
            'line ' . __LINE__ => [
                'Lebenslauf.pdf',
                'lebenslauf.pdf',
                false,
            ],
            'line ' . __LINE__ => [
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ.0987654321.jpg',
                'abcdefghijklmnopqrstuvwxyz.0987654321.jpg',
                false,
            ],
            'line ' . __LINE__ => [
                'abcdefghijklmnopqrstuvwxyz.0987654321.jpg',
                'abcdefghijklmnopqrstuvwxyz.0987654321.jpg',
                false,
            ],
            'line ' . __LINE__ => [
                'ÄÖÜ&äöü.gif',
                'aeoeue_aeoeue.gif',
                true,
            ],
            'line ' . __LINE__ => [
                '-_!"§$%&/()=?²³{[]}\^@€.jpg',
                '-_____________________eur.jpg',
                true,
            ],
            'line ' . __LINE__ => [
                '.png',
                'png',
                false,
            ],
            'line ' . __LINE__ => [
                '..png',
                'png',
                false,
            ],
            'line ' . __LINE__ => [
                'file.',
                'file',
                false,
            ],
            'line ' . __LINE__ => [
                '.',
                '',
                false,
            ],
            'line ' . __LINE__ => [
                '..',
                '',
                false,
            ],
        ];
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']);
}

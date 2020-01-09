<?php
/**
 *  Copyright notice.
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
 * benötigte Klassen einbinden.
 */
class tx_mkforms_tests_util_FormBaseTest extends tx_rnbase_tests_BaseTestCase
{
    /**
     * @group unit
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Please provide the parameter for 'configurationId'
     */
    public function testGetConfigurationValueThrowsExceptionIfNoCondifurationIdConfigured()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $form = tx_mkforms_tests_Util::getForm();

        tx_mkforms_util_FormBase::getConfigurationValue([], $form);
    }

    /**
     * @group unit
     */
    public function testGetConfigurationValue()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $form = tx_mkforms_tests_Util::getForm(
            true,
            tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                ['myConf.' => ['path' => 'test']]
            )
        );

        self::assertEquals(
            'test',
            tx_mkforms_util_FormBase::getConfigurationValue(
                ['configurationId' => 'myConf.path'],
                $form
            )
        );
    }

    /**
     * @group unit
     */
    public function testGetConfigurationValueDeep()
    {
        self::markTestIncomplete('RuntimeException: The requested database connection named "Default" has not been configured.');

        tx_rnbase_util_Misc::prepareTSFE();
        $form = tx_mkforms_tests_Util::getForm(
            true,
            tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                ['myConf.' => [
                        'path' => 'TEXT',
                        'path.' => ['value' => 'textvalue'],
                    ],
                ]
            )
        );

        self::assertEquals(
            'textvalue',
            tx_mkforms_util_FormBase::getConfigurationValue(
                ['configurationId' => 'myConf.path'],
                $form
            )
        );
    }

    /**
     * @group unit
     */
    public function testGetConfigurationValueIfCastToBoolean()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $form = tx_mkforms_tests_Util::getForm(
            true,
            tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                ['myConf.' => ['path' => 'test']]
            )
        );

        self::assertTrue(
            tx_mkforms_util_FormBase::getConfigurationValue(
                ['configurationId' => 'myConf.path', 'castToBoolean' => 1],
                $form
            )
        );
    }

    /**
     * @group unit
     */
    public function testGetConfigurationValueIfPrefixWithConfigurationIdOfForm()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $form = tx_mkforms_tests_Util::getForm(
            true,
            tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                ['generic.' => ['formconfig.' => ['myConf.' => ['path' => 'test']]]]
            )
        );
        self::assertEquals(
            'test',
            tx_mkforms_util_FormBase::getConfigurationValue(
                ['prefixWithConfigurationIdOfForm' => 1, 'configurationId' => 'myConf.path'],
                $form
            )
        );
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php'];
}

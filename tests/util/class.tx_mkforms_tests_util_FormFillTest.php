<?php
/**
 * @author Michael Wagner
 *  Copyright notice
 *
 *  (c) 2015 DMK E-BUSINESS GmbH <dev@dmk-business.de>
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
 * @author Michael Wagner
 */
class tx_mkforms_tests_util_FormFillTest extends tx_rnbase_tests_BaseTestCase
{
    /**
     * @group unit
     */
    public function testGetItemsFromDb()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $formBase = $this->getMock(
            'tx_mkforms_util_FormFill',
            array('getRowsFromDataBase')
        );
        $form = tx_mkforms_tests_Util::getForm();
        $formBase->expects(self::once())
            ->method('getRowsFromDataBase')
            ->with(array('someParams'), $form)
            ->will(self::returnValue(
                array(
                    0 => array(
                        '__value__' => 123, '__caption__' => 'first',
                    ),
                    1 => array(
                        '__value__' => 456, '__caption__' => 'second',
                    ),
                )
            ));

        self::assertEquals(
            array(
                0 => array('value' => 123, 'caption' => 'first'),
                1 => array('value' => 456, 'caption' => 'second'),
            ),
            $formBase->getItemsFromDb(array('someParams'), $form),
            'rückgabe falsch'
        );
    }

    public function testGetCountries()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('static_info_tables')) {
            self::markTestSkipped('Die Extension static_info_tables ist nicht installiert.');
        }

        $form = tx_mkforms_tests_Util::getForm();
        $formFill = $this->getMock(
            'tx_mkforms_util_FormFill',
            array('getItemsFromDb')
        );

        $formFill
            ->expects(self::once())
            ->method('getItemsFromDb')
            ->with()
            ->will(
                $this->returnValue(
                    array(
                        array('value' => '13', 'caption' => 'Oesterreich'),
                        array('value' => '54', 'caption' => 'Deutschland'),
                        array('value' => '41', 'caption' => 'Schweiz'),
                    )
                )
            );

        $countries = $formFill->getStaticCountries(
            array(
                'add_top_countries' => '54',
                'add_top_country_delimiter' => '---',
            ),
            $form
        );

        self::assertEquals(54, $countries[0]['value']);
        self::assertEquals(0, $countries[1]['value']);
        self::assertEquals('---', $countries[1]['caption']);
        self::assertEquals(13, $countries[2]['value']);
        self::assertEquals(41, $countries[3]['value']);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php'];
}

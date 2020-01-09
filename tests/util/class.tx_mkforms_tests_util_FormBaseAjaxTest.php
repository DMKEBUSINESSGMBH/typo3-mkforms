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

/**
 * Array util tests.
 */
class tx_mkforms_tests_util_FormBaseAjaxTest extends tx_rnbase_tests_BaseTestCase
{
    public function testRepaintDependenciesReturnsCorrectArray()
    {
        self::markTestIncomplete('Creating default object from empty value');

        $params = ['me' => 'fieldset__widget-listbox'];
        $ret = tx_mkforms_util_FormBaseAjax::repaintDependencies($params, tx_mkforms_tests_Util::getForm());
        // formidable_mainrenderlet::majixRepaintDependancies liefert immer ein array!
        $ret = $ret[0];
        self::assertContains('radioTestForm[fieldset][widget-checksingle]', $ret['data']);
        self::assertEquals('radioTestForm__fieldset__widget-checksingle', $ret['object'], 'Es wurde nicht das richtige object zurück gegeben!');
        self::assertEmpty($ret['databag'], 'Es wurde doch ein databag zurück gegeben!');
        self::assertEquals('repaint', $ret['method'], 'Es wurde nicht die richtige Methode zurück gegeben!');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php'];
}

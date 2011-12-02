<?php
/**
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <michael.wagner@das-medienkombinat.de>
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
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_mkforms_util_FormBaseAjax');
tx_rnbase::load('tx_mkforms_tests_Util');
	
/**
 * Array util tests
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_util
 */
class tx_mkforms_tests_util_FormBaseAjax_testcase extends tx_phpunit_testcase {
	
	public function testRepaintDependenciesReturnsCorrectArray(){#
		if(defined('TYPO3_cliMode') && TYPO3_cliMode){
			$this->markTestSkipped('Geht leider nicht unter CLI.');
		}
		$params = array('me' => 'fieldset__widget-listbox');
		$ret = tx_mkforms_util_FormBaseAjax::repaintDependencies($params, tx_mkforms_tests_Util::getForm());
		// formidable_mainrenderlet::majixRepaintDependancies liefert immer ein array!
		$ret = $ret[0];
		$this->assertEquals('<input type="checkbox" name="radioTestForm[fieldset][widget-checksingle]" id="radioTestForm__fieldset__widget-checksingle"  value="1" />',$ret['data'],'Es wurde nicht die richtige data zurück gegeben!');
		$this->assertEquals('radioTestForm__fieldset__widget-checksingle',$ret['object'],'Es wurde nicht das richtige object zurück gegeben!');
		$this->assertEmpty($ret['databag'],'Es wurde doch ein databag zurück gegeben!');
		$this->assertEquals('repaint',$ret['method'],'Es wurde nicht die richtige Methode zurück gegeben!');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']);
}
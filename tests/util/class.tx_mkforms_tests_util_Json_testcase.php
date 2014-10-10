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

/**
 * Array util tests
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_util
 */
class tx_mkforms_tests_util_Json_testcase extends tx_phpunit_testcase {

	protected function getNewInstance() {
		return tx_rnbase::makeInstance('tx_mkforms_util_Json', SERVICES_JSON_LOOSE_TYPE);
	}

	public function testUnicodeCharacterU2028() {
		$string = pack("H*", 'e280a8');
		$string = 'davor '.$string.' dazwischen'.$string.'dahinter';
		$json = $this->getNewInstance()->encode($string);
		$this->assertEquals('"davor \n dazwischen\ndahinter"', $json);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Json_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Json_testcase.php']);
}
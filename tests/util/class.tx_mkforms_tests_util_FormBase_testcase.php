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
tx_rnbase::load('tx_mkforms_util_FormBase');
tx_rnbase::load('tx_mkforms_tests_Util');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_util
 */
class tx_mkforms_tests_util_FormBase_testcase extends tx_rnbase_tests_BaseTestCase {

	/**
	 * @group unit
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Please provide the parameter for 'configurationId'
	 */
	public function testGetConfigurationValueThrowsExceptionIfNoCondifurationIdConfigured() {
		$form = tx_mkforms_tests_Util::getForm();

		tx_mkforms_util_FormBase::getConfigurationValue(array(), $form);
	}

	/**
	 * @group unit
	 */
	public function testGetConfigurationValue() {
		$form = tx_mkforms_tests_Util::getForm(
			true,
			tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
				tx_mkforms_tests_Util::getDefaultFormConfig(true),
				array('myConf.' => array('path' => 'test'))
			)
		);

		self::assertEquals(
			'test',
			tx_mkforms_util_FormBase::getConfigurationValue(
				array('configurationId' => 'myConf.path'), $form
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetConfigurationValueDeep() {
		tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!
		$form = tx_mkforms_tests_Util::getForm(
			true,
			tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
				tx_mkforms_tests_Util::getDefaultFormConfig(true),
				array('myConf.' => array(
						'path' => 'TEXT',
						'path.' => array('value' => 'textvalue'),
					)
				)
			)
		);

		self::assertEquals(
			'textvalue',
			tx_mkforms_util_FormBase::getConfigurationValue(
				array('configurationId' => 'myConf.path'), $form
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetConfigurationValueIfCastToBoolean() {
		$form = tx_mkforms_tests_Util::getForm(
			true,
			tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
				tx_mkforms_tests_Util::getDefaultFormConfig(true),
				array('myConf.' => array('path' => 'test'))
			)
		);

		self::assertTrue(
			tx_mkforms_util_FormBase::getConfigurationValue(
				array('configurationId' => 'myConf.path', 'castToBoolean' => 1), $form
			)
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/util/class.tx_mkforms_tests_util_Div_testcase.php']);
}

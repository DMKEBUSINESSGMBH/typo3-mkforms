<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_tests_api
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-business.de>
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
tx_rnbase::load('tx_mkforms_tests_Util');
require_once(tx_rnbase_util_Extensions::extPath('phpunit').'Classes/Framework.php');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');
tx_rnbase::load('tx_mkforms_tests_Util');

/**
 * Testfälle für tx_mkforms_api_mainrenderlet
 * wir testen am beispiel des TEXT widgets
 *
 * @author hbochmann
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_filter
 */
class tx_mkforms_tests_api_tx_ameosformidable_testcase extends tx_rnbase_tests_BaseTestCase {

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		tx_rnbase::load('tx_mklib_tests_Util');
		tx_mklib_tests_Util::prepareTSFE(array('force' => TRUE, 'initFEuser' => TRUE));

		$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', array());
		$GLOBALS['TSFE']->fe_user->storeSessionData();

		set_error_handler(array('tx_mkforms_tests_Util', 'errorHandler'), E_WARNING);
	}

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// error handler zurücksetzen
		restore_error_handler();
	}

	/**
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionCode 2001
	 * @expectedExceptionMessage Das Formular ist nicht valide
	 */
	public function testRenderThrowsExceptionIfRequestTokenIsNotSet() {
		$_POST['radioTestForm']["AMEOSFORMIDABLE_SUBMITTED"] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
		tx_mkforms_tests_Util::getForm()->render();
	}

	/**
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionCode 2001
	 * @expectedExceptionMessage Das Formular ist nicht valide
	 */
	public function testRenderThrowsExceptionIfRequestTokenIsInvalid() {
		$_POST['radioTestForm']["AMEOSFORMIDABLE_SUBMITTED"] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
		$_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = 'iAmInvalid';
		tx_mkforms_tests_Util::getForm()->render();
	}

	/**
	 */
	public function testRenderThrowsNoExceptionIfCsrfProtectionDeactivated() {
		$_POST['radioTestForm']["AMEOSFORMIDABLE_SUBMITTED"] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
		$_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = 'iAmInvalid';
		$oForm = tx_mkforms_tests_Util::getForm(false);

		self::assertNotContains(
			'<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->getCsrfProtectionToken().'" />',
			$oForm->render(),
			'Es ist nicht der richtige request token enthalten!'
		);
	}

	/**
	 */
	public function testRenderThrowsNoExceptionIfRequestTokenIsValid() {
		$_POST['radioTestForm']["AMEOSFORMIDABLE_SUBMITTED"] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
		//damit wir getCsrfProtectionToken aufrufen können
		$oForm = tx_mkforms_tests_Util::getForm();
		$_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = $oForm->getCsrfProtectionToken();
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', array('requestToken' => array($oForm->getFormId() => $oForm->getCsrfProtectionToken())));
		$GLOBALS['TSFE']->fe_user->storeSessionData();

		//jetzt die eigentliche initialisierung
		$oForm = tx_mkforms_tests_Util::getForm();

		self::assertContains(
			'<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->getCsrfProtectionToken().'" />',
			$oForm->render(),
			'Es ist nicht der richtige request token enthalten!'
		);
	}

	/**
	 */
	public function testGetCreationTimestamp() {
		$form = tx_mkforms_tests_Util::getForm();
		$GLOBALS['TSFE']->fe_user->setKey(
			'ses', 'mkforms', array('creationTimestamp' => array($form->getFormId() => 123))
		);
		$GLOBALS['TSFE']->fe_user->storeSessionData();

		$form = tx_mkforms_tests_Util::getForm();

		self::assertEquals(
			123,
			$form->getCreationTimestamp(),
			'falscher timestamp der Erstellung'
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']);
}


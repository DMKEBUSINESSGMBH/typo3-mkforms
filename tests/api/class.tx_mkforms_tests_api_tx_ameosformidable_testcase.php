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
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mkforms_tests_Util');
require_once(t3lib_extMgm::extPath('phpunit').'Classes/Framework.php');

/**
 * Testfälle für tx_mkforms_api_mainrenderlet
 * wir testen am beispiel des TEXT widgets
 *
 * @author hbochmann
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_filter
 */
class tx_mkforms_tests_api_tx_ameosformidable_testcase extends tx_phpunit_testcase {

	public function setUp() {
		$oTestFramework = tx_rnbase::makeInstance('Tx_Phpunit_Framework','mkforms');
		$oTestFramework->createFakeFrontEnd();
		/*
		 * warning "Cannot modify header information" abfangen.
		*
		* Einige Tests lassen sich leider nicht ausführen:
		* "Cannot modify header information - headers already sent by"
		* Diese wird an unterschiedlichen stellen ausgelöst,
		* meißt jedoch bei Session operationen
		* Ab Typo3 6.1 laufend die Tests auch auf der CLI nicht.
		* Eigentlich gibt es dafür die runInSeparateProcess Anotation,
		* Allerdings funktioniert diese bei Typo3 nicht, wenn versucht wird
		* die GLOBALS in den anderen Prozess zu Übertragen.
		* Ein Deaktivierend er Übertragung führt dazu,
		* das Typo3 nicht initialisiert ist.
		*
		* Wir gehen also erst mal den Weg, den Fehler abzufangen.
		*/
		set_error_handler(array(__CLASS__, 'errorHandler'), E_WARNING);
	}

	public function tearDown() {
		// error handler zurücksetzen
		restore_error_handler();
	}

	/**
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @param array $errcontext
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		$ignoreMsg = array(
				'Cannot modify header information - headers already sent by',
		);
		foreach($ignoreMsg as $msg) {
			if ((is_string($ignoreMsg) || is_numeric($ignoreMsg)) && strpos($errstr, $ignoreMsg) !== FALSE) {
				// Don't execute PHP internal error handler
				return FALSE;
			}
		}
		return NULL;
	}

	/**
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionCode 2001
	 * @expectedExceptionMessage Das Formular ist nicht valide
	 */
	public function testRenderThrowsExceptionIfRequestTokenIsNotSet() {
		$_POST['radioTestForm']["AMEOSFORMIDABLE_SUBMITTED"] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
		$oForm = tx_mkforms_tests_Util::getForm()->render();
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
		$oForm = tx_mkforms_tests_Util::getForm()->render();
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
}

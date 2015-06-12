<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_tests_action
 *  @author Michael Wagner
 *
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
***************************************************************/

tx_rnbase::load('tx_mkforms_forms_Factory');
require_once(t3lib_extMgm::extPath('phpunit').'Classes/Framework.php');


// @TODO: grundfunktionen in base testcase auslagern, um sie in anderen projekten zu nutzen!
class tx_mkforms_tests_action_FormBase_testcase extends tx_phpunit_testcase {
	protected $sCachefile;

	/**
	 *
	 */
	public function setUp() {

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

		$oTestFramework = tx_rnbase::makeInstance('Tx_Phpunit_Framework','mkforms');
		$oTestFramework->createFakeFrontEnd();

		unset($_POST['radioTestForm']);

		//aktuelle Konfiguration sichern
		//@todo funktionen aus mklib_tests_Util nutzen
		$this->sCachefile = $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'];
		//und für den test löschen
		$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] = null;

		$this->init();
	}

	public function tearDown() {
		//ursprüngliche Konfiguration wieder setzen
		//@todo funktionen aus mklib_tests_Util nutzen
		$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] = $this->sCachefile;

		unset($_POST['radioTestForm']);

		// error handler zurücksetzen
		restore_error_handler();
	}
	/**
	 * wir verwenden nicht mehr den constructor, da dieser zu oft aufgerufen wird.
	 */
	public function init() {
		static $bInit = false; if ($bInit) return; $bInit = true;
		/*
		 * t3lib_lock::acquire
		 * wenn in den looks die datei bereits existiert, kann es sein, das wir einen execution timeout bekommen
		 */

		// ts laden
		self::getStaticTS();
	}

	private static function getStaticTS(){
		tx_rnbase::load('tx_mkforms_tests_Util');
		return tx_mkforms_tests_Util::getStaticTS();
	}

	/**
	 *
	 * @param 	boolean 		$execute
	 * @return tx_mkforms_action_FormBase
	 */
	private static function &getAction($execute = true) {

		$configArray = self::getStaticTS();

		$configArray['generic.']['testmode'] = 1;
		$configArray['generic.']['xml'] = 'EXT:mkforms/tests/xml/renderlets.xml';
		$configArray['generic.']['addfields.']['widget-addfield'] = 'addfield feld';
		$configArray['generic.']['addfields.']['widget-remove'] = 'unset';
		$configArray['generic.']['addPostVars'] = 1;
		$configArray['generic.']['formconfig.']['loadJsFramework'] = 0;
		$configArray['generic.']['formconfig.']['csrfProtection'] = 1;

		$action = tx_rnbase::makeInstance('tx_mkforms_action_FormBase');

		$configurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');

		$configurations->init(
				$configArray,
				$configurations->getCObj(1),
				'mkforms', 'mkforms'
			);

		$configurations->setParameters($parameters);
		$action->setConfigurations($configurations);
		if($execute) {
			$out = $action->handleRequest($parameters, $configurations, $configurations->getViewData());
		}
		return $action;
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

public function test_processForm() {
		$sData = array(
				'fieldset' => array(
					'texte' => array(
						'input'=> array(
							'widget-text' => 'Eins',
							'widget1-widget2-text' => 'Zwei',
						),
						'area'=> array(
							'textarea' => 'Sehr Lang!'
						),
					),
					'widget-remove' => 'sollte entfernt werden',
					'widget-radiobutton' => '3',
					'widget-listbox' => '7',
					'widget-checkbox' => array(
						'item-5' => '6',
						'item-8' => '9',
					),
					'widget-date' => '426204000',
					'widgetlister' => array(
						1 => array(
							'listerdata-uid' => 1,
							'listerdata-title' => 'Titel 1',
							'listerdata-notInXml' => 'Titel 1',
						),
						2 => array(
							'listerdata-uid' => 2,
							'listerdata-title' => 'Titel 2',
						),
						3 => array(
							'listerdata-uid' => 3,
							'listerdata-title' => 'Titel 3',
						),
						4 => array(
							'listerdata-uid' => 4,
							'listerdata-title' => 'Titel 4',
						),
						5 => array(
							'listerdata-uid' => 5,
							'listerdata-title' => 'Titel 5',
						),
						'selected' => '5',
						'notInXml' => 'Titel 1',
					),
				),
				'widget-submit' => 'Daten absenden',
				'widget-thatDoesNotExistInTheXml' => 'valueThatShouldBeRemoved',
				'AMEOSFORMIDABLE_SERVEREVENT' => '',
				'AMEOSFORMIDABLE_SERVEREVENT_PARAMS' => '',
				'AMEOSFORMIDABLE_SERVEREVENT_HASH' => '',
				'AMEOSFORMIDABLE_ADDPOSTVARS' => '[{\"action\":\"formData\",\"params\":{\"widget-submit\":\"1\"}}]',
				'AMEOSFORMIDABLE_VIEWSTATE' => '',
				'AMEOSFORMIDABLE_SUBMITTED' => 'AMEOSFORMIDABLE_EVENT_SUBMIT_FULL',
				'AMEOSFORMIDABLE_SUBMITTER' => '',
				'MKFORMS_REQUEST_TOKEN' => self::getAction()->getForm()->getCsrfProtectionToken()
			);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', array('requestToken' => array(self::getAction()->getForm()->getFormId() => self::getAction()->getForm()->getCsrfProtectionToken())));
		$GLOBALS['TSFE']->fe_user->storeSessionData();

		$_POST['radioTestForm'] = $sData;

		$action = self::getAction();

		// die Daten werden für den view in formData gespeichert
		$formData = $action->getConfigurations()->getViewData()->offsetGet('formData');

		self::assertTrue(isset($formData['submitmode']), 'LINE:'.__LINE__);
		self::assertEquals($formData['submitmode'], 'full', 'LINE:'.__LINE__);

		self::assertTrue(is_array($formData['widget']), 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['text']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['text'], 'Eins', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['radiobutton']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['radiobutton'], '3', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['listbox']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['listbox'], '7', 'LINE:'.__LINE__);
		self::assertTrue(is_array($formData['widget']['checkbox']), 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['checkbox']['5']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['checkbox']['5'], '6', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['checkbox']['8']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['checkbox']['8'], '9', 'LINE:'.__LINE__);
		self::assertTrue(is_array($formData['widget1']), 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget1']['text']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget1']['text'], 'Zwei', 'LINE:'.__LINE__);
		self::assertTrue(is_array($formData['widget2']), 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget2']['text']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget2']['text'], 'Zwei', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['textarea']), 'LINE:'.__LINE__);
		self::assertEquals($formData['textarea'], 'Sehr Lang!', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widgetlister']['selected']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widgetlister']['selected'], '5', 'LINE:'.__LINE__);
		self::assertFalse(isset($formData['widgetlister']['notInXml']), 'LINE:'.__LINE__);
		for($i=1; $i <=5 ; $i++) {
			self::assertTrue(is_array($formData['widgetlister'][$i]['listerdata']), $i.' LINE:'.__LINE__);
			self::assertTrue(isset($formData['widgetlister'][$i]['listerdata']['uid']), $i.' LINE:'.__LINE__);
			self::assertEquals($formData['widgetlister'][$i]['listerdata']['uid'], $i, $i.' LINE:'.__LINE__);
			self::assertTrue(isset($formData['widgetlister'][$i]['listerdata']['title']), $i.' LINE:'.__LINE__);
			self::assertEquals($formData['widgetlister'][$i]['listerdata']['title'], 'Titel '.$i, $i.' LINE:'.__LINE__);
			//sollte entfernt werden da nicht im xml
			self::assertFalse(isset($formData['widgetlister'][$i]['listerdata']['notInXml']), $i.' LINE:'.__LINE__);
		}
		//submit
		self::assertTrue(isset($formData['widget']['submit']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['submit'], '1', 'LINE:'.__LINE__);
		//date
		self::assertTrue(isset($formData['widget']['date']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['date'], '426204000', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['widget']['date_mysql']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['date_mysql'], '1983-07-05', 'LINE:'.__LINE__);
		//addpostvars
		self::assertTrue(is_array($formData['addpostvars']), 'LINE:'.__LINE__);
		self::assertEquals($formData['addpostvars'][0]['action'], 'formData', 'LINE:'.__LINE__);
		self::assertTrue(isset($formData['addpostvars'][0]['params']['widget']['submit']), 'LINE:'.__LINE__);
		//addfields
		self::assertTrue(isset($formData['widget']['addfield']), 'LINE:'.__LINE__);
		self::assertEquals($formData['widget']['addfield'], 'addfield feld', 'LINE:'.__LINE__);
		self::assertFalse(isset($formData['widget']['remove']), 'LINE:'.__LINE__);

		//sollte entfernt werden
		self::assertFalse(isset($formData['widget']['thatDoesNotExistInTheXml']), 'LINE:'.__LINE__);
	}

	public function test_handleRequest() {
		$action = self::getAction();

		self::assertEquals('tx_mkforms_action_FormBase', get_class($action), 'Wrong class given.');
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionCode 2001
	 * @expectedExceptionMessage Das Formular ist nicht valide
	 */
	public function test_processFormThrowsExceptionWithInvalidRequestToken() {
		$sData = array(
				'AMEOSFORMIDABLE_SUBMITTED' => 'AMEOSFORMIDABLE_EVENT_SUBMIT_FULL',
				'MKFORMS_REQUEST_TOKEN' => 'iAmInvalid'
			);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', array('requestToken' => array(self::getAction()->getForm()->getFormId() => self::getAction()->getForm()->getCsrfProtectionToken())));
		$GLOBALS['TSFE']->fe_user->storeSessionData();

		$_POST['radioTestForm'] = $sData;

		$action = self::getAction();
	}

	public function test_fillForm() {
		$sData['widget']['text'] = 'Default Text';
		// die vorselektierten Werte für mehrere Checkboxen müssen kommasepariert angegebenw werden!
		$sData['widget']['checkbox'] = '8,6';

		$sData['widget']['remove'] = 'sollte entfernt werden';
		$sData['widget']['radiobutton'] = 7;
		$sData['widget']['listbox'] = 7;
		$sData['widget']['checksingle'] = 1;
		$sData['widget1']['text'] = 'Default Text 1';
		$sData['widget2']['text'] = 'Default Text 2';
		$sData['textarea'] = 'Ganz Langer vordefinierter Text';

		$action = self::getAction();
		$fillData = $action->fillForm($sData, $action->getForm());


		self::assertTrue(isset($fillData['widget-text']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-text'], 'Default Text', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget-checkbox']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-checkbox'], '8,6', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget-radiobutton']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-radiobutton'], '7', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget-listbox']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-listbox'], '7', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget-checksingle']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-checksingle'], '1', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget1-text']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget1-text'], 'Default Text 1', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget2-text']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget2-text'], 'Default Text 2', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['textarea']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['textarea'], 'Ganz Langer vordefinierter Text', 'LINE:'.__LINE__);
		self::assertTrue(isset($fillData['widget-widget1-widget2-text']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-widget1-widget2-text'], 'Default Text', 'LINE:'.__LINE__);

		//addfields
		self::assertTrue(isset($fillData['widget-addfield']), 'LINE:'.__LINE__);
		self::assertEquals($fillData['widget-addfield'], 'addfield feld', 'LINE:'.__LINE__);
		self::assertFalse(isset($fillData['widget-remove']), 'LINE:'.__LINE__);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/action/class.tx_mkforms_tests_action_FormBase_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/action/class.tx_mkforms_tests_action_FormBase_testcase.php']);
}

<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_tests_action
 *  @author Michael Wagner
 *
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
***************************************************************/

tx_rnbase::load('tx_mkforms_forms_Factory');
tx_rnbase::load('tx_mklib_tests_Util');

//$res = register_shutdown_function('shutdown');
//function shutdown(){
//    if ($error = error_get_last()) {
// 			t3lib_div::debug($error, 'DEBUG: '.__METHOD__.' Line: '.__LINE__); // @TODO: remove me
// 			tx_rnbase::load('tx_rnbase_util_Misc');
// 			tx_rnbase_util_Misc::mayday('error');
//    }
//}


class tx_mkforms_tests_action_FormBase_testcase extends tx_phpunit_testcase {
	protected $sCachefile;
	
	/**
	 *
	 */
	public function setUp() {
		//aktuelle Konfiguration sichern
		//@todo funktionen aus mklib_tests_Util nutzen
		$this->sCachefile = $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'];
		//und für den test löschen
		$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] = null;
	}
	
	public function tearDown() {
		//ursprüngliche Konfiguration wieder setzen
		//@todo funktionen aus mklib_tests_Util nutzen
		$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] = $this->sCachefile;
	}
	
	/**
	 * constructor
	 */
	public function tx_mkforms_tests_action_FormBase_testcase(){
		/*
		 * t3lib_lock::acquire
		 * wenn in den looks die datei bereits existiert, kann es sein, das wir einen execution timeout bekommen
		 */
		
		// logoff für phpmyadmin deaktivieren
		/*
		 * Error in test case test_handleRequest
		 * in file C:\xampp\htdocs\typo3\typo3conf\ext\phpmyadmin\res\class.tx_phpmyadmin_utilities.php
		 * on line 66:
		 * Message:
		 * Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\typo3\typo3conf\ext\phpunit\mod1\class.tx_phpunit_module1.php:112)
		 *
		 * Diese Fehler passiert, wenn die usersession ausgelesen wird. der feuser hat natürlich keine.
		 * Das Ganze passiert in der t3lib_userauth->fetchUserSession.
		 * Dort wird t3lib_userauth->logoff aufgerufen, da keine session vorhanden ist.
		 * phpmyadmin klingt sich da ein und schreibt daten in die session.
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing']))
		foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $k=>$v){
			if($v = 'tx_phpmyadmin_utilities->pmaLogOff'){
				unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$k]);
			}
		}
	}
	
	/**
	 *
	 * @param 	boolean 		$execute
	 * @return tx_mkforms_action_FormBase
	 */
	private static function &getAction($execute = true) {
		$configArray = array(
				'testmode' => 1,
				'xml' => 'EXT:mkforms/tests/xml/renderlets.xml',
				'addfields.' => array(
						'widget-addfield' => 'addfield feld',
						'widget-remove' => 'unset',
					),
				'fieldSeparator' => '-',
				'addPostVars' => 1,
				'formconfig.' => array('loadJsFramework' => 0), // formconfig für config check setzen.
			);
		$configArray = array('generic.' => $configArray);
		return tx_mklib_tests_Util::getAction('tx_mkforms_action_FormBase',$configArray,'mkforms');
	}
	public function test_handleRequest() {
		$action = $this->getAction();
		
		$this->assertEquals('tx_mkforms_action_FormBase', get_class($action), 'Wrong class given.');
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
					),
				),
				'widget-submit' => 'Daten absenden',
				'AMEOSFORMIDABLE_SERVEREVENT' => '',
				'AMEOSFORMIDABLE_SERVEREVENT_PARAMS' => '',
				'AMEOSFORMIDABLE_SERVEREVENT_HASH' => '',
				'AMEOSFORMIDABLE_ADDPOSTVARS' => '[{\"action\":\"formData\",\"params\":{\"widget-submit\":\"1\"}}]',
				'AMEOSFORMIDABLE_VIEWSTATE' => '',
				'AMEOSFORMIDABLE_SUBMITTED' => 'AMEOSFORMIDABLE_EVENT_SUBMIT_FULL',
				'AMEOSFORMIDABLE_SUBMITTER' => '',
			);
		
		$_POST['radioTestForm'] = $sData;
			
		$action = $this->getAction();
		
		// die Daten werden für den view in formData gespeichert
		$formData = $action->getConfigurations()->getViewData()->offsetGet('formData');
		
		$this->assertTrue(isset($formData['submitmode']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['submitmode'], 'full', 'LINE:'.__LINE__);
		
		$this->assertTrue(is_array($formData['widget']), 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['text']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['text'], 'Eins', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['radiobutton']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['radiobutton'], '3', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['listbox']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['listbox'], '7', 'LINE:'.__LINE__);
		$this->assertTrue(is_array($formData['widget']['checkbox']), 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['checkbox']['5']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['checkbox']['5'], '6', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['checkbox']['8']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['checkbox']['8'], '9', 'LINE:'.__LINE__);
		$this->assertTrue(is_array($formData['widget1']), 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget1']['text']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget1']['text'], 'Zwei', 'LINE:'.__LINE__);
		$this->assertTrue(is_array($formData['widget2']), 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget2']['text']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget2']['text'], 'Zwei', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['textarea']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['textarea'], 'Sehr Lang!', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widgetlister']['selected']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widgetlister']['selected'], '5', 'LINE:'.__LINE__);
		for($i=1; $i <=5 ; $i++) {
			$this->assertTrue(is_array($formData['widgetlister'][$i]['listerdata']), $i.' LINE:'.__LINE__);
			$this->assertTrue(isset($formData['widgetlister'][$i]['listerdata']['uid']), $i.' LINE:'.__LINE__);
			$this->assertEquals($formData['widgetlister'][$i]['listerdata']['uid'], $i, $i.' LINE:'.__LINE__);
			$this->assertTrue(isset($formData['widgetlister'][$i]['listerdata']['title']), $i.' LINE:'.__LINE__);
			$this->assertEquals($formData['widgetlister'][$i]['listerdata']['title'], 'Titel '.$i, $i.' LINE:'.__LINE__);
		}
		//submit
		$this->assertTrue(isset($formData['widget']['submit']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['submit'], '1', 'LINE:'.__LINE__);
		//date
		$this->assertTrue(isset($formData['widget']['date']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['date'], '426204000', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget']['date_mysql']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['widget']['date_mysql'], '1983-07-05', 'LINE:'.__LINE__);
		//addpostvars
		$this->assertTrue(is_array($formData['addpostvars']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['addpostvars'][0]['action'], 'formData', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['addpostvars'][0]['params']['widget']['submit']), 'LINE:'.__LINE__);
		//addfields
		$this->assertTrue(isset($formData['widget']['addfield']), 'LINE:'.__LINE__);
		$this->assertEquals(isset($formData['widget']['addfield']), 'addfield feld', 'LINE:'.__LINE__);
		$this->assertFalse(isset($formData['widget']['remove']), 'LINE:'.__LINE__);
		
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
		
		$action = $this->getAction();
		$fillData = $action->fillForm($sData, $action->getForm());
		
		
		$this->assertTrue(isset($fillData['widget-text']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-text'], 'Default Text', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget-checkbox']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-checkbox'], '8,6', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget-radiobutton']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-radiobutton'], '7', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget-listbox']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-listbox'], '7', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget-checksingle']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-checksingle'], '1', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget1-text']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget1-text'], 'Default Text 1', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget2-text']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget2-text'], 'Default Text 2', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['textarea']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['textarea'], 'Ganz Langer vordefinierter Text', 'LINE:'.__LINE__);
		$this->assertTrue(isset($fillData['widget-widget1-widget2-text']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-widget1-widget2-text'], 'Default Text', 'LINE:'.__LINE__);
		
		//addfields
		$this->assertTrue(isset($fillData['widget-addfield']), 'LINE:'.__LINE__);
		$this->assertEquals($fillData['widget-addfield'], 'addfield feld', 'LINE:'.__LINE__);
		$this->assertFalse(isset($fillData['widget-remove']), 'LINE:'.__LINE__);
		
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/action/class.tx_mkforms_tests_action_FormBase_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/action/class.tx_mkforms_tests_action_FormBase_testcase.php']);
}

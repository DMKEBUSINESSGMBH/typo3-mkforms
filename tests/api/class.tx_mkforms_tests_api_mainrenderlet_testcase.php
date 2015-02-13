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
require_once(t3lib_extMgm::extPath('mkforms') . 'api/class.mainobject.php');
require_once(t3lib_extMgm::extPath('mkforms') . 'api/class.mainrenderlet.php');
tx_rnbase::load('tx_mkforms_tests_Util');

/**
 * Testfälle für tx_mkforms_api_mainrenderlet
 * wir testen am beispiel des TEXT widgets
 *
 * @author hbochmann
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_filter
 */
class tx_mkforms_tests_api_mainrenderlet_testcase extends tx_phpunit_testcase {

	/**
	 * Unser Mainvalidator
	 * @var tx_ameosformidable
	 */
	protected $oForm;
	
	/**
	 * setUp() = init DB etc.
	 */
	public function setUp(){
		$this->oForm = tx_mkforms_tests_Util::getForm();
	}

	/**
	 * Prüft _isTooLongByChars mit Multi-byte zeichen und ohne
	 */
	public function testSetValueSanitizesStringIfConfigured() {
		//per default soll bereinigt werden
		$this->oForm->getWidget('widget-text')->setValue('<script>alert("ohoh");</script>');
		$this->assertEquals('<sc<x>ript>alert("ohoh");</script>',$this->oForm->getWidget('widget-text')->getValue(),'JS wurde nicht entfernt bei widget-text!');
		//hier ist sanitize auf false gesetzt
		$this->oForm->getWidget('widget-text2')->setValue('<script>alert("ohoh");</script>');
		$this->assertEquals('<script>alert("ohoh");</script>',$this->oForm->getWidget('widget-text2')->getValue(),'JS wurde nicht entfernt bei widget-text2!');
	}
	
	/**
	 * Performance bei hundertfachen aufruf von getvalue testen
	 * mit bereinigung
	 * sollte 3 mal so langsam wie ohne bereinigung sein
	 */
	public function testPerformanceOfSetValueWithSanitizingString() {
		if(defined('TYPO3_cliMode') && TYPO3_cliMode){
			$this->markTestSkipped('Dieser Test kann nur nur manuel für Analysen gestartet werden.');
		}
		$this->oForm->getWidget('widget-text')->setValue('<script type="text/javascript">alert("XSS");</script>');
		
		$dTime = microtime(true);
		// sind 100 aufrufe real? es sind sicher um einiges mehr.
		for ($i = 0; $i < 99; $i++) {
			$value = $this->oForm->getWidget('widget-text')->getValue();
		}
		$dUsedtime = microtime(true) -$dTime;
		
		//der grenzwert sollte nicht überschritten werden
		$this->assertLessThanOrEqual('0.1200000000000000', $dUsedtime, 'Das bereinigen des Values dauert zu lange und sollte refactorisiert werden.');
	}
	
	/**
	 * Performance bei hundertfachen aufruf von getvalue testen
	 * ohne bereinigung
	 * sollte 3 mal so schnell wie mit bereinigung sein
	 */
	public function testPerformanceOfSetValueWithoutSanitizingString() {
		if(defined('TYPO3_cliMode') && TYPO3_cliMode){
			$this->markTestSkipped('Dieser Test kann nur nur manuel für Analysen gestartet werden.');
		}
		$this->oForm->getWidget('widget-text2')->setValue('<script type="text/javascript">alert("XSS");</script>');
		
		$dTime = microtime(true);
		// sind 100 aufrufe real? es sind sicher um einiges mehr.
		for ($i = 0; $i < 99; $i++) {
			$value = $this->oForm->getWidget('widget-text2')->getValue();
		}
		$dUsedtime = microtime(true) -$dTime;
		//der grenzwert sollte nicht überschritten werden
		$this->assertLessThanOrEqual('0.0400000000000000', $dUsedtime, 'Das bereinigen des Values dauert zu lange und sollte refactorisiert werden.');
	}
	
	/**
	 * @group unit
	 */
	public function testGetValueForHtmlConvertsHtmlSpecialCharsCorrectIfIsString() {
		$mainRenderlet = tx_rnbase::makeInstance('formidable_mainrenderlet');

		$this->assertEquals(
			'&quot;test&quot;',$mainRenderlet->getValueForHtml('"test"'), 'falsche bereinigt'
		);
	}
	
	/**
	 * @group unit
	 */
	public function testGetValueForHtmlConvertsCurlyBracketsCorrect() {
		$mainRenderlet = tx_rnbase::makeInstance('formidable_mainrenderlet');

		$this->assertEquals(
			'&#123;test&#125;',$mainRenderlet->getValueForHtml('{test}'), 'falsche bereinigt'
		);
	}
	
	/**
	 * @group unit
	 */
	public function testGetValueForHtmlConvertsNotHtmlSpecialCharsCorrectIfIsArray() {
		$mainRenderlet = tx_rnbase::makeInstance('formidable_mainrenderlet');

		$this->assertEquals(
			array('test'),$mainRenderlet->getValueForHtml(array('test')), 'array bereinigt'
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']);
}

?>
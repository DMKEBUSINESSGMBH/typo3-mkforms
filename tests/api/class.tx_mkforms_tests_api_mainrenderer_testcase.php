<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_tests_api
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
require_once(t3lib_extMgm::extPath('mkforms') . 'api/class.mainrenderer.php');
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
class tx_mkforms_tests_api_mainrenderer_testcase extends tx_phpunit_testcase {

	public function setUp() {
		$oTestFramework = tx_rnbase::makeInstance('Tx_Phpunit_Framework','mkforms');
		$oTestFramework->createFakeFrontEnd();
	}

	/**
	 */
	public function testRenderInsertsCorrectRequestTokenIntoHtmlAndSession() {
		$oForm = tx_mkforms_tests_Util::getForm();
		$oRenderer = tx_rnbase::makeInstance('formidable_mainrenderer');
		$oRenderer->_init($oForm,array(),array(),'');

		$aRendered = $oRenderer->_render(array());

		$this->assertContains(
			'<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->getCsrfProtectionToken().'" />',
			$aRendered['HIDDEN'],
			'Es ist nicht der richtige request token enthalten!'
		);


		//requestToken auch in der session?
		$aSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
		$this->assertEquals(1,count($aSessionData['requestToken']),'mehr request tokens in der session als erwartet!');
		$this->assertEquals($aSessionData['requestToken']['radioTestForm'],$oForm->getCsrfProtectionToken(),'falscher request token in der session!');
	}

	/**
	 */
	public function testRenderInsertsCorrectRequestTokenIntoHtmlAndSessionIfRequestTokensExist() {
		$GLOBALS['TSFE']->fe_user->setKey(
			'ses', 'mkforms', array(
				'requestToken' => array(
					'firstForm' => 'secret',
					'secondForm' => 'anotherSecret',
				)
			)
		);
		$GLOBALS['TSFE']->fe_user->storeSessionData();


		$oForm = tx_mkforms_tests_Util::getForm();
		$oRenderer = tx_rnbase::makeInstance('formidable_mainrenderer');
		$oRenderer->_init($oForm,array(),array(),'');

		$aRendered = $oRenderer->_render(array());

		$this->assertContains(
			'<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->getCsrfProtectionToken().'" />',
			$aRendered['HIDDEN'],
			'Es ist nicht der richtige request token enthalten!'
		);


		//requestToken auch in der session?
		$aSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
		$this->assertEquals(3,count($aSessionData['requestToken']),'mehr request tokens in der session als erwartet!');
		$this->assertEquals($aSessionData['requestToken']['radioTestForm'],$oForm->getCsrfProtectionToken(),'falscher request token in der session!');
		//alte request tokens richtig?
		$this->assertEquals($aSessionData['requestToken']['firstForm'],'secret','falscher request token in der session!');
		$this->assertEquals($aSessionData['requestToken']['secondForm'],'anotherSecret','falscher request token in der session!');

	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']);
}

?>
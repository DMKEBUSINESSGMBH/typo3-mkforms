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
require_once(t3lib_extMgm::extPath('mkforms') . 'api/class.maindatahandler.php');
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
class tx_mkforms_tests_api_maindatahandler_testcase extends tx_phpunit_testcase {

	public function setUp() {
		$oTestFramework = tx_rnbase::makeInstance('Tx_Phpunit_Framework','mkforms');
		$oTestFramework->createFakeFrontEnd();
	}

	/**
	 */
	public function testGetRdtValueSubmitEditionRemovesValuesOfNoneWidgets() {
		$sData = array(
				'fieldset' => array(
					'texte' => array(
						'input'=> array(
							'widget-text' => 'Eins',
						),
						'widget-thatDoesNotExistInTheXml1' => 'valueThatShouldBeRemoved1',
					),
					'widget-checkbox' => array(
						'item-5' => '6',
						'item-8' => '9',
					),
					'widgetlister' => array(
						1 => array(
							'listerdata-uid' => 1,
							'listerdata-title' => 'Titel 1',
							'listerdata-thatdoednotexists' => 'Titel 1',
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
						'listerdata-thatdoednotexists' => 'Titel 1',
					),
					'widget-thatDoesNotExistInTheXml2' => 'valueThatShouldBeRemoved2',
				),
				'widget-thatDoesNotExistInTheXml3' => 'valueThatShouldBeRemoved3',
			);
		$_POST['radioTestForm'] = $sData;

		$oForm = tx_mkforms_tests_Util::getForm();
		$oHandler = tx_rnbase::makeInstance('formidable_maindatahandler');
		$oHandler->_init($oForm,array(),array(),'');

		//einzelnes renderlet
		$formData = $oHandler->getRdtValue_submit_edition('widget-thatDoesNotExistInTheXml3');
		//wert sollte auf null gesetzt werden
		$this->assertNull($formData,'wert für nicht existentes widget nicht auf null gesetzt');

		//renderlet box
		$formData = $oHandler->getRdtValue_submit_edition('fieldset');

		$this->assertTrue(isset($formData['texte']['input']['widget-text']), 'LINE:'.__LINE__);
		$this->assertEquals($formData['texte']['input']['widget-text'], 'Eins', 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widget-checkbox']), 'LINE:'.__LINE__);
		$this->assertEquals(array('item-5' => '6','item-8' => '9'),$formData['widget-checkbox'], 'LINE:'.__LINE__);
		$this->assertTrue(isset($formData['widgetlister']), 'LINE:'.__LINE__);
		$this->assertEquals(array(1 => array('listerdata-uid' => 1,'listerdata-title' => 'Titel 1',),2 => array('listerdata-uid' => 2,'listerdata-title' => 'Titel 2',),3 => array('listerdata-uid' => 3,'listerdata-title' => 'Titel 3',),4 => array('listerdata-uid' => 4,'listerdata-title' => 'Titel 4',),5 => array('listerdata-uid' => 5,'listerdata-title' => 'Titel 5',),'selected' => '5'),$formData['widgetlister'], 'LINE:'.__LINE__);

		//werte sollte entfernt wurden sein
		$this->assertFalse(isset($formData['texte']['widget-thatDoesNotExistInTheXml1']),'wert für nicht existentes widget nicht auf null gesetzt');
		$this->assertFalse(isset($formData['widget-thatDoesNotExistInTheXml2']),'wert für nicht existentes widget nicht auf null gesetzt');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/tests/api/class.tx_mkforms_tests_api_mainvalidator_testcase.php']);
}

?>

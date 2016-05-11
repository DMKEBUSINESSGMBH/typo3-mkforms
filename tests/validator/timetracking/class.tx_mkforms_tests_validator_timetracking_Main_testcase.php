<?php
/**
 *  Copyright notice
 *
 *  (c) 2015 DMK E-BUSINESS GmbH <dev@dmk-business.de>
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
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 *
 * tx_mkforms_tests_validator_timetracking_Main_testcase
 *
 * @package 		TYPO3
 * @subpackage	 	mkforms
 * @author 			Hannes Bochmann
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_tests_validator_timetracking_Main_testcase extends tx_rnbase_tests_BaseTestCase {

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		tx_rnbase::load('tx_mklib_tests_Util');
		tx_mklib_tests_Util::prepareTSFE(array('force' => TRUE, 'initFEuser' => TRUE));

		$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', array());
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}

	/**
	 * @group unit
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Please provide the threshold parameter for the tooFast validation
	 */
	public function testValidateThrowsExceptionIfValidationForTooFastButNoThresholdConfigured() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-toofast-without-threshold');
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
	}

	/**
	 * @group unit
	 */
	public function testValidateSetsNoValidationErrorIfFormNotSendTooFast() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-toofast');
		$this->setCreationTimestamp(1);
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
		self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
	}

	/**
	 * @group unit
	 */
	public function testValidateSetsValidationErrorIfFormSendTooFast() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-toofast');
		$this->setCreationTimestamp($GLOBALS['EXEC_TIME']);
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
		self::assertEquals(
			array('timetracking-toofast' => 'form send too fast'),
			$form->_aValidationErrors,
			'Es sind doch keine Validierungsfehler aufgetreten.'
		);
	}

	/**
	 * @group unit
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Please provide the threshold parameter for the tooFast validation
	 */
	public function testValidateThrowsExceptionIfValidationForTooSlowButNoThresholdConfigured() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-tooslow-without-threshold');
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
	}

	/**
	 * @group unit
	 */
	public function testValidateSetsNoValidationErrorIfFormNotSendTooSlow() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-tooslow');
		$this->setCreationTimestamp($GLOBALS['EXEC_TIME']);
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
		self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
	}

	/**
	 * @group unit
	 */
	public function testValidateSetsValidationErrorIfFormSendTooSlow() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-tooslow');
		$this->setCreationTimestamp(1);
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
		self::assertEquals(
			array('timetracking-tooslow' => 'form send too slow'),
			$form->_aValidationErrors,
			'Es sind doch keine Validierungsfehler aufgetreten.'
		);
	}

	/**
	 * @group unit
	 */
	public function testValidateSetsNoValidationErrorIfCanNoBeValidated() {
		/* @var $validator tx_mkforms_validator_timetracking_Main */
		$validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
		$form = $this->getForm();
		$timetrackingWidget = $form->getWidget('timetracking-tooslow-with-skipifempty');
		$timetrackingWidget->setValue('');
		$this->setCreationTimestamp(1);
		$validator->_init(
			$form, $timetrackingWidget->aElement['validators']['validator'], array(), ''
		);

		$validator->validate($timetrackingWidget);
		self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
	}

	/**
	 * @return tx_mkforms_forms_Base
	 */
	protected function getForm() {
		return tx_mkforms_tests_Util::getForm(
			TRUE,
			tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
				tx_mkforms_tests_Util::getDefaultFormConfig(TRUE),
				array('generic.' => array('xml' => 'EXT:mkforms/tests/xml/timetracking.xml'))
			)
		);
	}

	/**
	 * @param int $timestamp
	 * @return void
	 */
	protected function setCreationTimestamp($timestamp) {
		$GLOBALS['TSFE']->fe_user->setKey(
			'ses', 'mkforms', array(
				'creationTimestamp' => array(
					'timetrackingTestForm' => $timestamp,
				)
			)
		);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
}
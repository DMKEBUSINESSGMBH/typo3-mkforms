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
 * tx_mkforms_tests_validator_timetracking_Main_testcase
 *
 * @package         TYPO3
 * @subpackage      mkforms
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_tests_validator_timetracking_Main_testcase extends tx_rnbase_tests_BaseTestCase
{

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        tx_rnbase::load('tx_mklib_tests_Util');
        tx_mklib_tests_Util::prepareTSFE(['force' => true, 'initFEuser' => true]);

        $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', []);
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }

    /**
     * @group unit
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Please provide the threshold parameter for the tooFast validation
     */
    public function testValidateThrowsExceptionIfValidationForTooFastButNoThresholdConfigured()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast-without-threshold');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        $validator->validate($timetrackingWidget);
    }

    /**
     * @group unit
     */
    public function testValidateSetsNoValidationErrorIfFormNotSendTooFast()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );
        $this->setCreationTimestamp(1);

        $validator->validate($timetrackingWidget);
        self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
    }

    /**
     * @group unit
     */
    public function testValidateSetsValidationErrorIfFormSendTooFast()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast');
        $this->setCreationTimestamp($GLOBALS['EXEC_TIME']);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        $validator->validate($timetrackingWidget);
        self::assertEquals(
            ['timetracking-toofast' => 'form send too fast'],
            $form->_aValidationErrors,
            'Es sind doch keine Validierungsfehler aufgetreten.'
        );
    }

    /**
     * @group unit
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Please provide the threshold parameter for the tooFast validation
     */
    public function testValidateThrowsExceptionIfValidationForTooSlowButNoThresholdConfigured()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-tooslow-without-threshold');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        $validator->validate($timetrackingWidget);
    }

    /**
     * @group unit
     */
    public function testValidateSetsNoValidationErrorIfFormNotSendTooSlow()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-tooslow');
        $this->setCreationTimestamp($GLOBALS['EXEC_TIME']);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        $validator->validate($timetrackingWidget);
        self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
    }

    /**
     * @group unit
     */
    public function testValidateSetsValidationErrorIfFormSendTooSlow()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-tooslow');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );
        $this->setCreationTimestamp(1);

        $validator->validate($timetrackingWidget);
        self::assertEquals(
            ['timetracking-tooslow' => 'form send too slow'],
            $form->_aValidationErrors,
            'Es sind doch keine Validierungsfehler aufgetreten.'
        );
    }

    /**
     * @group unit
     */
    public function testValidateSetsNoValidationErrorIfCanNoBeValidated()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-tooslow-with-skipifempty');
        $timetrackingWidget->setValue('');
        $this->setCreationTimestamp(1);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        $validator->validate($timetrackingWidget);
        self::assertEmpty($form->_aValidationErrors, 'Es sind doch Validierungsfehler aufgetreten.');
    }

    /**
     * @group unit
     */
    public function testGetThresholdByValidationKey()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast');
        $this->setCreationTimestamp(1);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        self::assertEquals(10, $this->callInaccessibleMethod($validator, 'getThresholdByValidationKey', 'toofast'));
    }

    /**
     * @group unit
     */
    public function testGetThresholdByValidationKeyWithRunable()
    {
        /* @var $validator tx_mkforms_validator_timetracking_Main */
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast-with-runable-for-threshold');
        $this->setCreationTimestamp(1);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );

        self::assertEquals(123, $this->callInaccessibleMethod($validator, 'getThresholdByValidationKey', 'toofast'));
    }

    /**
     * @return tx_mkforms_forms_Base
     */
    protected function getForm()
    {
        return tx_mkforms_tests_Util::getForm(
            true,
            tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                ['generic.' => ['xml' => 'EXT:mkforms/tests/xml/timetracking.xml']]
            )
        );
    }

    /**
     * @param int $timestamp
     * @return void
     */
    protected function setCreationTimestamp($timestamp)
    {
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses',
            'mkforms',
            [
                'creationTimestamp' => [
                    'timetrackingTestForm' => $timestamp,
                ]
            ]
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }

    /**
     * @return number
     */
    public function getThresholdForRunable()
    {
        return 123;
    }

    /**
     */
    public function testHandleAfterRenderCheckPointInsertsCorrectCreationTimeIntoSession()
    {
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );
        $validator->handleAfterRenderCheckPoint();

        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
        self::assertCount(1, $sessionData['creationTimestamp'],
            'der timestamp für die Erstellung des Formulars nicht in der Session'
        );
        self::assertEquals(
            $GLOBALS['EXEC_TIME'],
            $sessionData['creationTimestamp']['timetrackingTestForm'],
            'falscher timestamp in der session!'
        );
    }

    /**
     */
    public function testHandleAfterRenderCheckPointInsertsCorrectCreationTimeIntoSessionIfAlreadyTimestampsInSession()
    {
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses',
            'mkforms',
            [
                'creationTimestamp' => [
                    'firstForm' => 123,
                    'secondForm' => 456,
                ]
            ]
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $timetrackingWidget = $form->getWidget('timetracking-toofast');
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );
        $validator->handleAfterRenderCheckPoint();

        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
        self::assertCount(3, $sessionData['creationTimestamp'],
            'der timestamp für die Erstellung des Formulars nicht in der Session'
        );
        self::assertEquals(
            $GLOBALS['EXEC_TIME'],
            $sessionData['creationTimestamp']['timetrackingTestForm'],
            'falscher timestamp in der session!'
        );

        self::assertEquals(
            123,
            $sessionData['creationTimestamp']['firstForm'],
            'falscher timestamp in der session von firstForm!'
        );
        self::assertEquals(
            456,
            $sessionData['creationTimestamp']['secondForm'],
            'falscher timestamp in der session von secondForm!'
        );
    }

    /**
     */
    public function testHandleAfterRenderCheckPointInsertsNoCreationTimeIntoSessionWhenPluginIsNotUserInt()
    {

        $validator = tx_rnbase::makeInstance('tx_mkforms_validator_timetracking_Main');
        $form = $this->getForm();
        $contentObjectRendererClass = tx_rnbase_util_Typo3Classes::getContentObjectRendererClass();
        $form->getConfigurations()->getCObj()->setUserObjectType($contentObjectRendererClass::OBJECTTYPE_USER);
        // init gets called when the form is initialized. so we need to reset the session data
        // und call init again to see if everything works as expected
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', []);
        $validator->_init(
            $form,
            $timetrackingWidget->aElement['validators']['validator'],
            [],
            ''
        );
        $validator->handleAfterRenderCheckPoint();

        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
        self::assertArrayNotHasKey('creationTimestamp', $sessionData);
    }
}

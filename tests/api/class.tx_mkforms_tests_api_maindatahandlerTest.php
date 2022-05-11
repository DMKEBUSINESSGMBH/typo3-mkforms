<?php
/**
 * @author Hannes Bochmann
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
 * Testfälle für tx_mkforms_api_mainrenderlet
 * wir testen am beispiel des TEXT widgets.
 *
 * @author hbochmann
 */
class tx_mkforms_tests_api_maindatahandlerTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    public function setUp(): void
    {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms').'/ext_localconf.php';
    }

    public function testGetRdtValueSubmitEditionRemovesValuesOfNoneWidgets()
    {
        $sData = [
                'fieldset' => [
                    'texte' => [
                        'input' => [
                            'widget-text' => 'Eins',
                        ],
                        'widget-thatDoesNotExistInTheXml1' => 'valueThatShouldBeRemoved1',
                    ],
                    'widget-checkbox' => [
                        'item-5' => '6',
                        'item-8' => '9',
                    ],
                    'widgetlister' => [
                        1 => [
                            'listerdata-uid' => 1,
                            'listerdata-title' => 'Titel 1',
                            'listerdata-thatdoednotexists' => 'Titel 1',
                        ],
                        2 => [
                            'listerdata-uid' => 2,
                            'listerdata-title' => 'Titel 2',
                        ],
                        3 => [
                            'listerdata-uid' => 3,
                            'listerdata-title' => 'Titel 3',
                        ],
                        4 => [
                            'listerdata-uid' => 4,
                            'listerdata-title' => 'Titel 4',
                        ],
                        5 => [
                            'listerdata-uid' => 5,
                            'listerdata-title' => 'Titel 5',
                        ],
                        'selected' => '5',
                        'listerdata-thatdoednotexists' => 'Titel 1',
                    ],
                    'widget-thatDoesNotExistInTheXml2' => 'valueThatShouldBeRemoved2',
                ],
                'widget-thatDoesNotExistInTheXml3' => 'valueThatShouldBeRemoved3',
            ];
        $_POST['radioTestForm'] = $sData;

        $oForm = tx_mkforms_tests_Util::getForm();
        $oHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_maindatahandler');
        $oHandler->_init($oForm, [], [], '');

        // einzelnes renderlet
        $formData = $oHandler->getRdtValue_submit_edition('widget-thatDoesNotExistInTheXml3');
        // wert sollte auf null gesetzt werden
        self::assertNull($formData, 'wert für nicht existentes widget nicht auf null gesetzt');

        // renderlet box
        $formData = $oHandler->getRdtValue_submit_edition('fieldset');

        self::assertTrue(isset($formData['texte']['input']['widget-text']), 'LINE:'.__LINE__);
        self::assertEquals($formData['texte']['input']['widget-text'], 'Eins', 'LINE:'.__LINE__);
        self::assertTrue(isset($formData['widget-checkbox']), 'LINE:'.__LINE__);
        self::assertEquals(['item-5' => '6', 'item-8' => '9'], $formData['widget-checkbox'], 'LINE:'.__LINE__);
        self::assertTrue(isset($formData['widgetlister']), 'LINE:'.__LINE__);
        self::assertEquals([1 => ['listerdata-uid' => 1, 'listerdata-title' => 'Titel 1'], 2 => ['listerdata-uid' => 2, 'listerdata-title' => 'Titel 2'], 3 => ['listerdata-uid' => 3, 'listerdata-title' => 'Titel 3'], 4 => ['listerdata-uid' => 4, 'listerdata-title' => 'Titel 4'], 5 => ['listerdata-uid' => 5, 'listerdata-title' => 'Titel 5'], 'selected' => '5'], $formData['widgetlister'], 'LINE:'.__LINE__);

        // werte sollte entfernt wurden sein
        self::assertFalse(isset($formData['texte']['widget-thatDoesNotExistInTheXml1']), 'wert für nicht existentes widget nicht auf null gesetzt');
        self::assertFalse(isset($formData['widget-thatDoesNotExistInTheXml2']), 'wert für nicht existentes widget nicht auf null gesetzt');
    }
}

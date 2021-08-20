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
 * benötigte Klassen einbinden.
 */
require_once tx_rnbase_util_Extensions::extPath('mkforms').'api/class.mainobject.php';
require_once tx_rnbase_util_Extensions::extPath('mkforms').'api/class.mainvalidator.php';

/**
 * Testfälle für tx_mkforms_util_Solr.
 *
 * @author hbochmann
 */
class tx_mkforms_tests_api_mainvalidatorTest extends tx_rnbase_tests_BaseTestCase
{
    /**
     * Unser Mainvalidator.
     *
     * @var formidable_mainvalidator
     */
    protected $oMainValidator;

    /**
     * Form.
     *
     * @var tx_ameosformidable
     */
    protected $oForm;

    /**
     * setUp() = init DB etc.
     */
    public function setUp()
    {
        $this->oMainValidator = tx_rnbase::makeInstance('formidable_mainvalidator');
        self::markTestIncomplete('Line below throws multiple errors:'.
            'call_user_func_array() expects parameter 1 to be a valid callback, first array member is not a valid class name or object'.
            'Creating default object from empty value');
        $this->oForm = tx_mkforms_tests_Util::getForm();
        $this->oMainValidator->_init($this->oForm, null, null, null);

        //evtl. aus vorherigen Tests
        $_POST = null;
    }

    /**
     * Prüft _isTooLongByChars mit Multi-byte zeichen und ohne.
     */
    public function testIsTooLongByChars()
    {
        // UTF-8 Text: 'The € - ä ö ü';
        $utf8Str = tx_rnbase_util_Strings::hexArr2bin(unserialize('a:18:{i:0;s:2:"54";i:1;s:2:"68";i:2;s:2:"65";i:3;s:2:"20";i:4;s:2:"e2";i:5;s:2:"82";i:6;s:2:"ac";i:7;s:2:"20";i:8;s:2:"2d";i:9;s:2:"20";i:10;s:2:"c3";i:11;s:2:"a4";i:12;s:2:"20";i:13;s:2:"c3";i:14;s:2:"b6";i:15;s:2:"20";i:16;s:2:"c3";i:17;s:2:"bc";}'));

        self::assertFalse($this->oMainValidator->_isTooLongByChars($utf8Str, 14), 'Es wurde nicht die korrekte Länge für den UTF-8 String erkannt. Maximale Anzahl: 14; Zeichenlänge:13');
        self::assertFalse($this->oMainValidator->_isTooLongByChars($utf8Str, 13), 'Es wurde nicht die korrekte Länge für den UTF-8 String erkannt. Maximale Anzahl: 13; Zeichenlänge:13');
        self::assertTrue($this->oMainValidator->_isTooLongByChars($utf8Str, 12), 'Es wurde nicht die korrekte Länge für den UTF-8 String erkannt. Maximale Anzahl: 12; Zeichenlänge:13');

        // ISO-Text: 'The EUR - ä ö ü';
        $iso8Str = tx_rnbase_util_Strings::hexArr2bin(unserialize('a:15:{i:0;s:2:"54";i:1;s:2:"68";i:2;s:2:"65";i:3;s:2:"20";i:4;s:2:"45";i:5;s:2:"55";i:6;s:2:"52";i:7;s:2:"20";i:8;s:2:"2d";i:9;s:2:"20";i:10;s:2:"e4";i:11;s:2:"20";i:12;s:2:"f6";i:13;s:2:"20";i:14;s:2:"fc";}'));
        self::assertFalse($this->oMainValidator->_isTooLongByChars($iso8Str, 16, 'latin1'), 'Es wurde nicht die korrekte Länge für den ISO String erkannt. Maximale Anzahl: 16; Zeichenlänge:15');
        self::assertFalse($this->oMainValidator->_isTooLongByChars($iso8Str, 15, 'latin1'), 'Es wurde nicht die korrekte Länge für den ISO String erkannt. Maximale Anzahl: 15; Zeichenlänge:15');
        self::assertTrue($this->oMainValidator->_isTooLongByChars($iso8Str, 14, 'latin1'), 'Es wurde nicht die korrekte Länge für den ISO String erkannt. Maximale Anzahl: 14; Zeichenlänge:15');
    }

    public function testOneRdtHasAValueReturnsTrueIfNothingHasAValue()
    {
        self::assertTrue($this->oMainValidator->_oneRdtHasAValue(0, 'fieldset__texte__input__widget-text'), 'Es wurde nicht false zurück gegeben!');
    }

    public function testOneRdtHasAValueReturnsFalseIfGivenRdtHasAValueAndSelfNot()
    {
        $this->oForm->getWidget('fieldset__texte__input__widget-text')->setValue(1);
        self::assertFalse($this->oMainValidator->_oneRdtHasAValue(0, 'fieldset__texte__input__widget-text'), 'Es wurde nicht true zurück gegeben!');
    }

    public function testOneRdtHasAValueReturnsFalseIfSelfHasAValueAndGivenRdtNot()
    {
        self::assertFalse($this->oMainValidator->_oneRdtHasAValue(2, 'fieldset__texte__input__widget-text'), 'Es wurde nicht true zurück gegeben!');
    }

    public function testHasThisOrDependentAValueReturnsFalseIfBothHaveAValue()
    {
        $this->oForm->getWidget('fieldset__texte__input__widget-text')->setValue(1);
        self::assertFalse($this->oMainValidator->_oneRdtHasAValue(2, 'fieldset__texte__input__widget-text'), 'Es wurde nicht true zurück gegeben!');
    }

    public function testCheckDependsOnReturnsTrueWhenDependentWidgetHasValue()
    {
        $this->oForm->getWidget('fieldset__texte__area__textarea')->setValue('sometext');

        $this->oMainValidator->aElement = [
            'type' => 'STANDARD',
            'required' => [
                'message' => 'Fehlermeldung',
                'dependson' => 'fieldset__texte__area__textarea',
            ],
        ];

        $widget = $this->oForm->getWidget('fieldset__widget-radiobutton');

        self::assertTrue(
            $this->callInaccessibleMethod(
                [$this->oMainValidator, 'checkDependsOn'],
                [&$widget, 'required']
            ),
            'Es wurde nicht true zurück gegeben!'
        );
    }

    public function testCheckDependsOnReturnsFalseWhenDependentWidgetHasNoValue()
    {
        $this->oForm->getWidget('fieldset__texte__area__textarea')->setValue('');

        $this->oMainValidator->aElement = [
            'type' => 'STANDARD',
            'required' => [
                'message' => 'Fehlermeldung',
                'dependson' => 'fieldset__texte__area__textarea',
            ],
        ];

        $widget = $this->oForm->getWidget('fieldset__widget-radiobutton');

        self::assertFalse(
            $this->callInaccessibleMethod(
                [$this->oMainValidator, 'checkDependsOn'],
                [&$widget, 'required']
            ),
            'Es wurde nicht false zurück gegeben!'
        );
    }
}

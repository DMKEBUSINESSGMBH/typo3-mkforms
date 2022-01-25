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
class tx_mkforms_tests_api_mainrenderletTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    /**
     * @var bool
     */
    public static $validatorWasCalled = false;

    /**
     * Unser Mainvalidator.
     *
     * @var tx_ameosformidable
     */
    protected $oForm;

    /**
     * @var unknown
     */
    protected $languageBackup;

    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        self::markTestIncomplete(
            'Line below throws multiple errors:'.
            'call_user_func_array() expects parameter 1 to be a valid callback, first array member is not a valid class name or object'.
            'Creating default object from empty value'
        );
        $this->oForm = tx_mkforms_tests_Util::getForm();
        $this->languageBackup = $GLOBALS['LANG']->lang;
    }

    /**
     * (non-PHPdoc).
     *
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        $GLOBALS['LANG']->lang = $this->languageBackup;
        self::$validatorWasCalled = false;
    }

    /**
     * Prüft _isTooLongByChars mit Multi-byte zeichen und ohne.
     */
    public function testGetValueSanitizesStringIfConfigured()
    {
        //per default soll bereinigt werden
        $this->oForm->getWidget('widget-text')->setValue('<script>alert("ohoh");</script>');
        self::assertEquals(
            '&lt;script&gt;alert(&quot;ohoh&quot;);&lt;/script&gt;',
            $this->oForm->getWidget('widget-text')->getValue(),
            'JS wurde nicht entfernt bei widget-text!'
        );
        //hier ist sanitize auf false gesetzt
        $this->oForm->getWidget('widget-text2')->setValue('<script>alert("ohoh");</script>');
        self::assertEquals('<script>alert("ohoh");</script>', $this->oForm->getWidget('widget-text2')->getValue(), 'JS wurde nicht entfernt bei widget-text2!');

        // jetzt überschreiben wir die konfiguration
        $this->oForm->getWidget('widget-text2')->forceSanitization();
        self::assertEquals(
            '&lt;script&gt;alert(&quot;ohoh&quot;);&lt;/script&gt;',
            $this->oForm->getWidget('widget-text2')->getValue(),
            'JS wurde nicht entfernt bei widget-text2!'
        );

        // jetzt überschreiben wir die konfiguration erneut
        $this->oForm->getWidget('widget-text2')->forceSanitization(false);
        self::assertEquals('<script>alert("ohoh");</script>', $this->oForm->getWidget('widget-text2')->getValue(), 'JS wurde nicht entfernt bei widget-text2!');

        // überschreibt das definitv den konfigurierten Wert
        $this->oForm->getWidget('widget-text')->forceSanitization(false);
        self::assertEquals(
            '<script>alert("ohoh");</script>',
            $this->oForm->getWidget('widget-text')->getValue(),
            'JS wurde doch entfernt bei widget-text!'
        );
    }

    /**
     * Performance bei hundertfachen aufruf von getvalue testen
     * mit bereinigung
     * sollte 3 mal so langsam wie ohne bereinigung sein.
     */
    public function testPerformanceOfSetValueWithSanitizingString()
    {
        if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
            $this->markTestSkipped('Dieser Test kann nur nur manuell für Analysen gestartet werden.');
        }
        $this->oForm->getWidget('widget-text')->setValue('<script>alert("XSS");</script>');

        $dTime = microtime(true);
        // sind 100 aufrufe real? es sind sicher um einiges mehr.
        for ($i = 0; $i < 99; ++$i) {
            $this->oForm->getWidget('widget-text')->getValue();
        }
        $dUsedtime = microtime(true) - $dTime;

        //der grenzwert sollte nicht überschritten werden
        self::assertLessThanOrEqual('0.1200000000000000', $dUsedtime, 'Das bereinigen des Values dauert zu lange und sollte refactorisiert werden.');
    }

    /**
     * Performance bei hundertfachen aufruf von getvalue testen
     * ohne bereinigung
     * sollte 3 mal so schnell wie mit bereinigung sein.
     */
    public function testPerformanceOfSetValueWithoutSanitizingString()
    {
        if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
            $this->markTestSkipped('Dieser Test kann nur nur manuell für Analysen gestartet werden.');
        }
        $this->oForm->getWidget('widget-text2')->setValue('<script>alert("XSS");</script>');

        $dTime = microtime(true);
        // sind 100 aufrufe real? es sind sicher um einiges mehr.
        for ($i = 0; $i < 99; ++$i) {
            $this->oForm->getWidget('widget-text2')->getValue();
        }
        $dUsedtime = microtime(true) - $dTime;
        //der grenzwert sollte nicht überschritten werden
        self::assertLessThanOrEqual('0.0400000000000000', $dUsedtime, 'Das bereinigen des Values dauert zu lange und sollte refactorisiert werden.');
    }

    /**
     * @group unit
     */
    public function testGetValueForHtmlConvertsHtmlSpecialCharsCorrectIfIsWidgetWithSanitzingDisabled()
    {
        $mainRenderlet = $this->oForm->getWidget('widget-text2');

        self::assertEquals(
            '&quot;test&quot;',
            $mainRenderlet->getValueForHtml('"test"'),
            'falsche bereinigt'
        );
    }

    /**
     * @group unit
     */
    public function testGetValueForHtmlConvertsHtmlSpecialCharsNotIfIsWidgetWithSanitzingEnabled()
    {
        $mainRenderlet = $this->oForm->getWidget('widget-text');

        self::assertEquals(
            '"test"',
            $mainRenderlet->getValueForHtml('"test"'),
            'falsche bereinigt'
        );
    }

    /**
     * @group unit
     */
    public function testGetValueForHtmlConvertsCurlyBracketsCorrect()
    {
        $mainRenderlet = $this->oForm->getWidget('widget-text2');

        self::assertEquals(
            '&#123;test&#125;',
            $mainRenderlet->getValueForHtml('{test}'),
            'falsche bereinigt'
        );
    }

    /**
     * @group unit
     */
    public function testGetValueForHtmlConvertsNotHtmlSpecialCharsCorrectIfIsArray()
    {
        $mainRenderlet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_mainrenderlet');

        self::assertEquals(
            ['test'],
            $mainRenderlet->getValueForHtml(['test']),
            'array bereinigt'
        );
    }

    /**
     * @group unit
     */
    public function testGetAddInputParamsArrayWithPlaceholderDefinedAsLocallangLabel()
    {
        $GLOBALS['LANG']->lang = 'default';
        $addInputParams = $this->oForm
                            ->getWidget('widget-text-with-placeholder')
                            ->_getAddInputParamsArray();

        $placeholderFound = false;
        foreach ($addInputParams as $addInputParam) {
            if (false !== strpos($addInputParam, 'placeholder')) {
                self::assertEquals(
                    'placeholder="Jump to last page"',
                    $addInputParam,
                    'placeholder falsch'
                );
                $placeholderFound = true;
            }
        }
        self::assertTrue($placeholderFound, 'placeholder attribut nicht gefunden');
    }

    /**
     * @group unit
     */
    public function testGetAddInputParamsArrayWithNoPlaceholder()
    {
        $addInputParams = $this->oForm
                            ->getWidget('widget-text')
                            ->_getAddInputParamsArray();

        $placeholderFound = false;
        foreach ($addInputParams as $addInputParam) {
            if (false !== strpos($addInputParam, 'placeholder')) {
                $placeholderFound = true;
            }
        }
        self::assertFalse($placeholderFound, 'placeholder attribut gefunden');
    }

    /**
     * @group unit
     */
    public function testAfterRenderCheckPointHandledCorrect()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['validators']['HANDLESAFTERRENDERCHECKPOINT'] = [
            'key' => 'tx_mkforms_tests_fixtures_ValidatorHandlesAfterRenderCheckpoint',
        ];
        // if the method handleAfterRenderCheckPoint would be called on all validators
        // without checkingif the method exists we would get a fatal error
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['validators']['HANDLESAFTERRENDERCHECKPOINTNOT'] = [
            'key' => 'tx_mkforms_tests_fixtures_ValidatorHandlesAfterRenderCheckpointNot',
        ];
        self::assertFalse(self::$validatorWasCalled);
        $form = tx_mkforms_tests_Util::getForm(
            false,
            \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(false),
                ['generic.' => ['xml' => 'EXT:mkforms/tests/xml/afterRenderCheckPointHandledCorrect.xml']]
            )
        );
        $form->render();
        self::assertTrue(self::$validatorWasCalled);
    }
}

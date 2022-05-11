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

/**
 * Testfälle für tx_mkforms_api_mainrenderlet
 * wir testen am beispiel des TEXT widgets.
 *
 * @author hbochmann
 */
class tx_mkforms_tests_api_tx_ameosformidableTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    protected function setUp(): void
    {
        self::markTestIncomplete('RuntimeException: The requested database connection named "Default" has not been configured.');
        \DMK\Mklib\Utility\Tests::prepareTSFE(['force' => true, 'initFEuser' => true]);

        $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', []);
        $GLOBALS['TSFE']->fe_user->storeSessionData();

        set_error_handler(['tx_mkforms_tests_Util', 'errorHandler'], E_WARNING);
    }

    protected function tearDown(): void
    {
        // error handler zurücksetzen
        restore_error_handler();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionCode 2001
     * @expectedExceptionMessage Das Formular ist nicht valide
     */
    public function testRenderThrowsExceptionIfRequestTokenIsNotSet()
    {
        $_POST['radioTestForm']['AMEOSFORMIDABLE_SUBMITTED'] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
        tx_mkforms_tests_Util::getForm()->render();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionCode 2001
     * @expectedExceptionMessage Das Formular ist nicht valide
     */
    public function testRenderThrowsExceptionIfRequestTokenIsInvalid()
    {
        $_POST['radioTestForm']['AMEOSFORMIDABLE_SUBMITTED'] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
        $_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = 'iAmInvalid';
        tx_mkforms_tests_Util::getForm()->render();
    }

    public function testRenderThrowsNoExceptionIfCsrfProtectionDeactivated()
    {
        $_POST['radioTestForm']['AMEOSFORMIDABLE_SUBMITTED'] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
        $_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = 'iAmInvalid';
        $oForm = tx_mkforms_tests_Util::getForm(false);

        self::assertNotContains(
            '<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->generateRequestToken().'" />',
            $oForm->render(),
            'Es ist nicht der richtige request token enthalten!'
        );
    }

    public function testRenderThrowsNoExceptionIfRequestTokenIsValid()
    {
        $_POST['radioTestForm']['AMEOSFORMIDABLE_SUBMITTED'] = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
        // damit wir generateRequestToken aufrufen können
        $oForm = tx_mkforms_tests_Util::getForm();
        $_POST['radioTestForm']['MKFORMS_REQUEST_TOKEN'] = $oForm->generateRequestToken();
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', ['requestToken' => [$oForm->getFormId() => $oForm->generateRequestToken()]]);
        $GLOBALS['TSFE']->fe_user->storeSessionData();

        // jetzt die eigentliche initialisierung
        $oForm = tx_mkforms_tests_Util::getForm();

        self::assertContains(
            '<input type="hidden" name="radioTestForm[MKFORMS_REQUEST_TOKEN]" id="radioTestForm_MKFORMS_REQUEST_TOKEN" value="'.$oForm->generateRequestToken().'" />',
            $oForm->render(),
            'Es ist nicht der richtige request token enthalten!'
        );
    }

    public function testGetCreationTimestamp()
    {
        $form = tx_mkforms_tests_Util::getForm();
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses',
            'mkforms',
            ['creationTimestamp' => [$form->getFormId() => 123]]
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();

        $form = tx_mkforms_tests_Util::getForm();

        self::assertEquals(
            123,
            $form->getCreationTimestamp(),
            'falscher timestamp der Erstellung'
        );
    }

    /**
     * @group unit
     */
    public function testGenerateRequestToken()
    {
        $form = tx_mkforms_tests_Util::getForm();

        $requestToken = $form->generateRequestToken();
        self::assertNotNull($form->generateRequestToken(), 'der Token ist leer');
        self::assertInternalType('string', $requestToken, 'der Token ist kein string');
        self::assertGreaterThan(8, strlen($requestToken), 'der Token ist nicht mind. 8 Zeichen lang');
        self::assertEquals($requestToken, $form->generateRequestToken(), 'der 2. Token gleicht nicht dem 1.');
    }

    /**
     * @group unit
     * @dataProvider dataProviderIsCsrfProtectionActive
     */
    public function testIsCsrfProtectionActive($typoScriptConfiguration, $expectedReturn)
    {
        $form = tx_mkforms_tests_Util::getForm(
            true,
            \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                $typoScriptConfiguration
            )
        );
        self::assertEquals($expectedReturn, $form->isCsrfProtectionActive());
    }

    /**
     * @return array
     */
    public function dataProviderIsCsrfProtectionActive()
    {
        return [
            [
                ['generic.' => ['formconfig.' => ['csrfProtection' => true]]], true,
            ],
            [
                ['generic.' => ['formconfig.' => ['csrfProtection' => false]]], false,
            ],
            [
                [
                    'generic.' => [
                        'formconfig.' => ['csrfProtection' => true],
                        'xml' => 'EXT:mkforms/tests/xml/withoutCsrfProtection.xml',
                    ],
                ],
                false,
            ],
            [
                [
                    'generic.' => [
                        'formconfig.' => ['csrfProtection' => false],
                        'xml' => 'EXT:mkforms/tests/xml/withoutCsrfProtection.xml',
                    ],
                ],
                false,
            ],
            [
                [
                    'generic.' => [
                        'formconfig.' => ['csrfProtection' => true],
                        'xml' => 'EXT:mkforms/tests/xml/withCsrfProtection.xml',
                    ],
                ],
                true,
            ],
            [
                [
                    'generic.' => [
                        'formconfig.' => ['csrfProtection' => false],
                        'xml' => 'EXT:mkforms/tests/xml/withCsrfProtection.xml',
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * @group unit
     * @dataProvider dataProviderIsCsrfProtectionActive
     */
    public function testIsCsrfProtectionActiveWhenPluginIsNoUserInt($typoScriptConfiguration)
    {
        $form = tx_mkforms_tests_Util::getForm(
            true,
            \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                tx_mkforms_tests_Util::getDefaultFormConfig(true),
                $typoScriptConfiguration
            )
        );
        $contentObjectRendererClass = \Sys25\RnBase\Utility\Typo3Classes::getContentObjectRendererClass();
        $form->getConfigurations()->getCObj()->setUserObjectType($contentObjectRendererClass::OBJECTTYPE_USER);
        self::assertFalse($form->isCsrfProtectionActive());
    }

    /**
     * @group unit
     */
    public function testGetFormActionWhenActionIsTransparent()
    {
        self::assertEquals(\Sys25\RnBase\Utility\T3General::getIndpEnv('REQUEST_URI'), tx_mkforms_tests_Util::getForm()->getFormAction());
    }
}

<?php
/**
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-business.de>
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
tx_rnbase::load('tx_mkforms_util_Templates');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests_util
 */
class tx_mkforms_tests_util_Templates_testcase extends tx_rnbase_tests_BaseTestCase
{

    /**
     * @group unit
     */
    public function testSanitizeStringForTemplateEngine()
    {
        self::assertEquals(
            '&#123;test&#125;',
            tx_mkforms_util_Templates::sanitizeStringForTemplateEngine('{test}'),
            'string falsch bereinigt'
        );
    }

    /**
     * @group unit
     */
    public function testParseTemplateCodeIncludesSubtemplates()
    {
        $templatesUtility = tx_mkforms_util_Templates::createInstance(tx_mkforms_tests_Util::getForm());
        $template = '<!-- ### INCLUDE_TEMPLATE EXT:mkforms/tests/fixtures/subtemplate.html@SUBPART ### -->';
        $parsedTemplate = $templatesUtility->parseTemplateCode($template, []);

        self::assertEquals('Subtemplate parsed', $parsedTemplate, 'Subtemplate nicht eingebunden');
    }
}

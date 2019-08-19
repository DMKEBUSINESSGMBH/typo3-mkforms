<?php
/**
 *  Copyright notice.
 *
 *  (c) 2014 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
 * FLUIDVIEWHELPER Testcase.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 */
class tx_mkforms_tests_widgets_fluidviewhelperTest extends tx_rnbase_tests_BaseTestCase
{
    /**
     * @group unit
     */
    public function testGetArguments()
    {
        $widget = $this->getWidgetMock(array('getArguments'));

        $parsedParams = $this->callInaccessibleMethod($widget, 'getArguments');

        self::assertCount(3, $parsedParams);
        self::assertEquals('DebugTitle', $parsedParams['title']);
        self::assertEquals(4, $parsedParams['maxDepth']);
        self::assertEquals('Hello World', $parsedParams['plainText']);
    }

    /**
     * @group unit
     */
    public function testRender()
    {
        $widget = $this->getWidgetMock(array('_render'));

        $helper = $this->getAccessibleMock(
            '\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper'
        );

        $helper
            ->expects(self::once())
            ->method('initializeArgumentsAndRender')
            ->with()
            ->will(self::returnValue('DEBUGCONTENT'));
        $widget
            ->expects(self::once())
            ->method('getViewHelper')
            ->with()
            ->will(self::returnValue($helper));
        $widget
            ->expects(self::once())
            ->method('getLabel')
            ->with()
            ->will(self::returnValue('LABEL:'));
        $widget
            ->expects(self::once())
            ->method('_displayLabel')
            ->with()
            ->will($this->returnArgument(0));
        $htmlBag = $this->callInaccessibleMethod($widget, '_render');

        self::assertCount(4, $htmlBag);
        self::assertEquals('LABEL:DEBUGCONTENT', $htmlBag['__compiled']);
        self::assertEquals('DEBUGCONTENT', $htmlBag['rendered']);
        self::assertEquals('LABEL:', $htmlBag['label']);
        self::assertEquals('DebugTitle', $htmlBag['value']);
    }

    /**
     * @group unit
     */
    public function testRenderWithErrors()
    {
        $widget = $this->getWidgetMock(array('_render'));

        $helper = $this->getAccessibleMock(
            '\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper'
        );

        $helper
            ->expects(self::once())
            ->method('initializeArgumentsAndRender')
            ->with()
            ->will($this->throwException(new Exception('PHPUnitException', 5050)));
        $widget
            ->expects(self::once())
            ->method('getViewHelper')
            ->with()
            ->will(self::returnValue($helper));
        $widget
            ->expects(self::once())
            ->method('getLabel')
            ->with()
            ->will(self::returnValue(''));
        $htmlBag = $this->callInaccessibleMethod($widget, '_render');

        self::assertCount(6, $htmlBag);
        self::assertEquals('<span class="error">PHPUnitException</span>', $htmlBag['__compiled']);
        self::assertEquals('<span class="error">PHPUnitException</span>', $htmlBag['rendered']);
        self::assertEquals('', $htmlBag['label']);
        self::assertEquals('DebugTitle', $htmlBag['value']);
        self::assertEquals(true, $htmlBag['renderError']);
        self::assertEquals(true, is_array($htmlBag['renderError.']));
        self::assertEquals(5050, $htmlBag['renderError.']['code']);
        self::assertEquals('PHPUnitException', $htmlBag['renderError.']['message']);
    }

    /**
     * build mock for the widget.
     *
     * @param array $allowedMethods
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWidgetMock(array $allowedMethods = array())
    {
        $mockedMethods = array_flip(
            array_keys(
                $this->getAllClassMethods('tx_mkforms_widgets_fluidviewhelper_Main')
            )
        );
        // methoden entfernen, die nicht gemockt werden sollen.
        foreach ($allowedMethods as $method) {
            unset($mockedMethods[$method]);
        }
        $widget = $this->getMock(
            'tx_mkforms_widgets_fluidviewhelper_Main',
            array_keys($mockedMethods)
        );
        $widget
            ->expects(self::any())
            ->method('getValue')
            ->with()
            ->will(self::returnValue('DebugTitle'));
        $widget
            ->expects(self::any())
            ->method('getParams')
            ->with()
            ->will(
                self::returnValue(
                    array(
                        'title' => 'rdt:value',
                        'maxDepth' => 4,
                        'plainText' => 'Hello World',
                    )
                )
            );

        return $widget;
    }

    /**
     * liefert alle möglichen methoden einer klasse.
     * unabhängig von der deklaration.
     *
     * @param unknown $class
     *
     * @return multitype:unknown
     */
    protected function getAllClassMethods($class)
    {
        $methods = array();
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods() as $method) {
            $methods[$method->getName()] = $method;
        }

        return $methods;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/widgets/class.tx_mkforms_tests_widgets_fluidviewhelper_testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/widgets/class.tx_mkforms_tests_widgets_fluidviewhelper_testcase.php'];
}

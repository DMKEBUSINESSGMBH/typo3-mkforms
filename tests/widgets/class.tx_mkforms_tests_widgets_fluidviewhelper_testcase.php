<?php
/**
 *  Copyright notice
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');
tx_rnbase::load('tx_mkforms_widgets_fluidviewhelper_Main');


/**
 * FLUIDVIEWHELPER Testcase
 *
 * @package tx_mkforms
 * @subpackage tx_mkforms_tests
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 */
class tx_mkforms_tests_widgets_fluidviewhelper_testcase
	extends tx_rnbase_tests_BaseTestCase {

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		if (!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			$this->markTestSkipped('TYPO3 6.2 required');
		}
	}

	/**
	 * @group unit
	 */
	public function testGetArguments() {
		if (!tx_rnbase::load('\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper')) {
			$this->markTestSkipped('Required DebugViewHelper does not exists for the tests.');
		}

		$widget = $this->getWidgetMock(array('getArguments'));

		$parsedParams = $this->callInaccessibleMethod($widget, 'getArguments');

		$this->assertCount(3, $parsedParams);
		$this->assertEquals('DebugTitle', $parsedParams['title']);
		$this->assertEquals(4, $parsedParams['maxDepth']);
		$this->assertEquals('Hello World', $parsedParams['plainText']);
	}

	/**
	 * @group unit
	 */
	public function testRender() {
		if (!tx_rnbase::load('\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper')) {
			$this->markTestSkipped('Required DebugViewHelper does not exists for the tests.');
		}

		$widget = $this->getWidgetMock(array('_render'));

		$helper = $this->getAccessibleMock(
			'\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper'
		);

		$helper
			->expects($this->once())
			->method('initializeArgumentsAndRender')
			->with()
			->will($this->returnValue('DEBUGCONTENT'))
		;
		$widget
			->expects($this->once())
			->method('getViewHelper')
			->with()
			->will($this->returnValue($helper))
		;
		$widget
			->expects($this->once())
			->method('getLabel')
			->with()
			->will($this->returnValue('LABEL:'))
		;
		$widget
			->expects($this->once())
			->method('_displayLabel')
			->with()
			->will($this->returnArgument(0))
		;
		$htmlBag = $this->callInaccessibleMethod($widget, '_render');

		$this->assertCount(4, $htmlBag);
		$this->assertEquals('LABEL:DEBUGCONTENT', $htmlBag['__compiled']);
		$this->assertEquals('DEBUGCONTENT', $htmlBag['rendered']);
		$this->assertEquals('LABEL:', $htmlBag['label']);
		$this->assertEquals('DebugTitle', $htmlBag['value']);
	}

	/**
	 * @group unit
	 */
	public function testRenderWithErrors() {
		if (!tx_rnbase::load('\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper')) {
			$this->markTestSkipped('Required DebugViewHelper does not exists for the tests.');
		}

		$widget = $this->getWidgetMock(array('_render'));

		$helper = $this->getAccessibleMock(
			'\\TYPO3\\CMS\\Fluid\\ViewHelpers\\DebugViewHelper'
		);

		$helper
			->expects($this->once())
			->method('initializeArgumentsAndRender')
			->with()
			->will($this->throwException(new Exception('PHPUnitException', 5050)))
		;
		$widget
			->expects($this->once())
			->method('getViewHelper')
			->with()
			->will($this->returnValue($helper))
		;
		$widget
			->expects($this->once())
			->method('getLabel')
			->with()
			->will($this->returnValue(''))
		;
		$htmlBag = $this->callInaccessibleMethod($widget, '_render');

		$this->assertCount(6, $htmlBag);
		$this->assertEquals('<span class="error">PHPUnitException</span>', $htmlBag['__compiled']);
		$this->assertEquals('<span class="error">PHPUnitException</span>', $htmlBag['rendered']);
		$this->assertEquals('', $htmlBag['label']);
		$this->assertEquals('DebugTitle', $htmlBag['value']);
		$this->assertEquals(TRUE, $htmlBag['renderError']);
		$this->assertEquals(TRUE, is_array($htmlBag['renderError.']));
		$this->assertEquals(5050, $htmlBag['renderError.']['code']);
		$this->assertEquals('PHPUnitException', $htmlBag['renderError.']['message']);
	}

	/**
	 * build mock for the widget
	 *
	 * @param array $allowedMethods
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getWidgetMock(array $allowedMethods = array()) {
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
			->expects($this->any())
			->method('getValue')
			->with()
			->will($this->returnValue('DebugTitle'))
		;
		$widget
			->expects($this->any())
			->method('getParams')
			->with()
			->will(
				$this->returnValue(
					array(
						'title' => 'rdt:value',
						'maxDepth' => 4,
						'plainText' => 'Hello World',
					)
				)
			)
		;

		return $widget;
	}

	/**
	 * liefert alle möglichen methoden einer klasse.
	 * unabhängig von der deklaration
	 *
	 * @param unknown $class
	 * @return multitype:unknown
	 */
	protected function getAllClassMethods($class) {
		$methods = array();
		tx_rnbase::load($class);
		$reflection = new ReflectionClass($class);
		foreach($reflection->getMethods() as $method) {
			$methods[$method->getName()] = $method;
		}
		return $methods;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/widgets/class.tx_mkforms_tests_widgets_fluidviewhelper_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/tests/widgets/class.tx_mkforms_tests_widgets_fluidviewhelper_testcase.php']);
}

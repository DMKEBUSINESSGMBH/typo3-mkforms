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

tx_rnbase::load('tx_rnbase_tests_BaseTestCase');
tx_rnbase::load('tx_mkforms_widgets_text_Main');

/**
 *
 * tx_mkforms_tests_widgets_text_Main_testcase
 *
 * @package 		TYPO3
 * @subpackage	 	mkforms
 * @author 			Hannes Bochmann <dev@dmk-ebusiness.de>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_tests_widgets_text_Main_testcase
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
	public function testRenderSetsDefaultTypeIfNoInputTypeIsConfigured() {
		$widget = $this->getWidgetMock(array('_render', 'getInputType'));

		$widget->expects(self::once())
			->method('_navConf')
			->with('/inputtype')
			->will(self::returnValue(''));

		$htmlBag = $this->callInaccessibleMethod($widget, '_render');

		self::assertEquals(
			'<input type="text" name="" id="" value=""  />',
			$htmlBag['input']
		);
	}

	/**
	 * @group unit
	 */
	public function testRenderSetsConfiguredInputType() {
		$widget = $this->getWidgetMock(array('_render', 'getInputType'));

		$widget->expects(self::once())
			->method('_navConf')
			->with('/inputtype')
			->will(self::returnValue('email'));

		$htmlBag = $this->callInaccessibleMethod($widget, '_render');

		self::assertEquals(
			'<input type="email" name="" id="" value=""  />',
			$htmlBag['input']
		);
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
				$this->getAllClassMethods('tx_mkforms_widgets_text_Main')
			)
		);
		// methoden entfernen, die nicht gemockt werden sollen.
		foreach ($allowedMethods as $method) {
			unset($mockedMethods[$method]);
		}
		$widget = $this->getMock(
			'tx_mkforms_widgets_text_Main',
			array_keys($mockedMethods)
		);
		$widget
			->expects(self::any())
			->method('getValue')
			->with()
			->will(self::returnValue('DebugTitle'))
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
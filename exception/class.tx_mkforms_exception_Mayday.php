<?php
/**
 * @package    tx_mkforms
 * @subpackage tx_mkforms_exception
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-BUSINESS GmbH <dev@dmk-business.de>
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
 ***************************************************************/
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_rnbase_util_Exception');

/**
 * Exception for mayday.
 *
 * @package    tx_mkforms
 * @subpackage tx_mkforms_action
 * @author     Michael Wagner <dev@dmk-business.de>
 */
class tx_mkforms_exception_Mayday extends tx_rnbase_util_Exception {

	/**
	 * Liefert den Stacktrace und konvertiert ihn (htmlspecialchars).
	 * Verhindert das die Exception-E-Mail zerstört werden,
	 * da hier immer unvollständiger HTML-Code enthalten ist!
	 *
	 * @return    string
	 */
	public function __toString() {
		$stack = parent::__toString();

		// html  konvertieren, damit die exception mail nicht zerstört wird!
		return htmlspecialchars($stack);
	}

	/**
	 * Liefert zusätzliche Daten für die Exception-E-Mail.
	 *
	 * @return    string
	 */
	public function getAdditional() {
		$additional = parent::getAdditional();

		return is_array($additional) ? print_r($additional, TRUE) : $additional;
	}
}

if (defined('TYPO3_MODE')
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/exception/class.tx_mkforms_exception_Mayday.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/exception/class.tx_mkforms_exception_Mayday.php']);
}
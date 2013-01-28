<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 René Nitzsche (nitzsche@das-medienkombinat.de)
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

require_once(t3lib_extMgm::extPath('mkforms') . 'api/class.tx_ameosformidable.php');

/**
 * Factory for forms.
 * 
 */
interface tx_mkforms_forms_IJSFramework {
	public function getBaseIncludes();
	public function includeBase();
	public function getEffectIncludes();
	public function includeTooltips();
	public function includeDragDrop();
	/**
	 * Liefert den Namen des JSFrameworks: jquery, prototype usw.
	 * Dieser Wert wird von Widgets verwendet um zusätzliche Dateien abhängig vom aktuellen Framework einzubinden.
	 * @return string
	 */
	public function getId();
}

interface tx_mkforms_forms_IPageInclude {
	public function getPagePath();
	public function getServerPath();
	public function getKey();
	/**
	 * Bei einem JS-Incluce true, bei CSS false
	 * @return string
	 */
	public function isJS();
	public function isFirstPos();
	public function getBeforeKey();
	public function getAfterKey();
}

class tx_mkforms_forms_PageInclude implements tx_mkforms_forms_IPageInclude {
	private $pagePath, $serverPath, $key, $firstPos, $beforeKey, $afterKey, $isJS;

	public static function createInstance($pagePath, $serverPath='', $sKey = '', $isJS=true, $bFirstPos = FALSE, $sBefore = '', $sAfter = '') {
		return new tx_mkforms_forms_PageInclude($pagePath, $serverPath, $sKey, $isJS, $bFirstPos, $sBefore, $sAfter);
	}
	public function __construct($pagePath, $serverPath='', $sKey = '', $isJS=true, $bFirstPos = FALSE, $sBefore = '', $sAfter = '') {
		$this->pagePath = $pagePath;
		$this->serverPath = $serverPath;
		$this->key = $sKey;
		$this->firstPos = $bFirstPos;
		$this->beforeKey = $sBefore;
		$this->afterKey = $sAfter;
		$this->isJS = $isJS;
	}
	
	public function getPagePath() {
		return $this->pagePath;
	}
	public function getServerPath() {
		return $this->serverPath;
	}
	public function getKey() {
		return $this->key;
	}
	public function isFirstPos() {
		return $this->firstPos;
	}
	public function getBeforeKey() {
		return $this->beforeKey;
	}
	public function getAfterKey() {
		return $this->afterKey;
	}
	public function isJS() {
		return $this->isJS;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/forms/class.tx_mkforms_forms_IJSFramework.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/forms/class.tx_mkforms_forms_IJSFramework.php']);
}
?>
<?php

class formidable_mainobject {

	var $oForm = NULL;

	var $aElement = NULL;

	var $sExtPath = NULL;

	var $sExtRelPath = NULL;

	var $sExtWebPath = NULL;

	var $aObjectType = NULL;

	var $sXPath = NULL;

	var $sNamePrefix = FALSE;

	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {

		$this->oForm =& $oForm;
		$this->aElement = $aElement;
		$this->aObjectType = $aObjectType;

		$this->sExtPath = $aObjectType['PATH'];
		$this->sExtRelPath = $aObjectType['RELPATH'];
		$this->sExtWebPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->sExtRelPath;

		$this->sXPath = $sXPath;

		$this->sNamePrefix = $sNamePrefix;

		$this->conf = $this->getForm()->getConfTS($aObjectType['OBJECT'] . '.' . $aObjectType['EXTKEY'] . '.');
		$this->conf = $this->conf ? $this->conf : array();
	}

	/**
	 * Returns the form
	 *
	 * @return tx_ameosformidable
	 */
	protected function &getForm() {
		return $this->oForm;
	}

	function _getType() {
		return $this->aElement['type'];
	}

	function _navConf($path, $aConf = FALSE) {
		if ($aConf !== FALSE) {
			return $this->getForm()->_navConf($path, $aConf);
		}

		return $this->getForm()->_navConf($path, $this->aElement);
	}

	function _isTrue($sPath, $aConf = FALSE) {
		return $this->_isTrueVal(
			$this->_navConf($sPath, $aConf)
		);
	}

	function isTrue($sPath, $aConf = FALSE) {
		return $this->_isTrue($sPath, $aConf);
	}

	function _isFalse($sPath) {
		$mValue = $this->_navConf($sPath);

		if ($mValue !== FALSE) {
			return $this->_isFalseVal($mValue);
		} else {
			return FALSE;    // if not found in conf, the searched value is not FALSE, so _isFalse() returns FALSE !!!!
		}
	}

	function isFalse($sPath) {
		return $this->_isFalse($sPath);
	}

	function _isTrueVal($mVal) {
		$mVal = $this->callRunneable($mVal);

		return (($mVal === TRUE) || ($mVal == '1') || (strtoupper($mVal) == 'TRUE'));
	}

	function isTrueVal($mVal) {
		return $this->_isTrueVal($mVal);
	}

	function _isFalseVal($mVal) {
		if ($this->oForm->isRunneable($mVal)) {
			$mVal = $this->callRunneable($mVal);
		}

		return (($mVal == FALSE) || (strtoupper($mVal) == 'FALSE'));
	}

	function isFalseVal($mVal) {
		return $this->_isFalseVal($mVal);
	}

	function _defaultTrue($sPath, $aConf = FALSE) {

		if ($this->_navConf($sPath, $aConf) !== FALSE) {
			return $this->_isTrue($sPath, $aConf);
		} else {
			return TRUE;    // TRUE as a default
		}
	}

	function _defaultFalse($sPath, $aConf = FALSE) {
		if ($this->_navConf($sPath, $aConf) !== FALSE) {
			return $this->_isTrue($sPath, $aConf);
		} else {
			return FALSE;    // FALSE as a default
		}
	}

	/**
	 * alias for _defaultTrue()
	 */
	function defaultTrue($sPath, $aConf = FALSE) {
		return $this->_defaultTrue($sPath, $aConf);
	}

	/**
	 * alias for _defaultFalse()
	 */
	function defaultFalse($sPath, $aConf = FALSE) {
		return $this->_defaultFalse($sPath, $aConf);
	}

	function _defaultTrueMixed($sPath) {
		if (($mMixed = $this->_navConf($sPath)) !== FALSE) {

			if (strtoupper($mMixed) !== 'TRUE' && strtoupper($mMixed) !== 'FALSE') {
				return $mMixed;
			}

			return $this->_isTrue($sPath);
		} else {
			return TRUE;    // TRUE as a default
		}
	}

	function defaultTrueMixed($sPath) {
		return $this->_defaultTrueMixed($sPath);
	}

	function _defaultFalseMixed($sPath) {
		if (($mMixed = $this->_navConf($sPath)) !== FALSE) {

			if (strtoupper($mMixed) !== 'TRUE' && strtoupper($mMixed) !== 'FALSE') {
				return $mMixed;
			}

			return $this->_isTrue($sPath);
		} else {
			return FALSE;    // FALSE as a default
		}
	}

	function defaultFalseMixed($sPath) {
		return $this->_defaultFalseMixed($sPath);
	}

	// this has to be static !!!
	function loaded(&$aParams) {
	}

	function cleanBeforeSession() {
		$this->baseCleanBeforeSession();
		unset($this->oForm);
	}

	function baseCleanBeforeSession() {
	}

	function awakeInSession(&$oForm) {
		$this->oForm =& $oForm;
	}

	function setParent(&$oParent) {
		/* nothing in main object */
	}

	/**
	 *  TODO: Diese Methode entfernen
	 * Alternativer Aufruf:
	 * return $this->getForm()->getRunnable()->callRunnable($mMixed, $this);
	 */
	function &callRunneable($mMixed) {
		$aArgs = func_get_args();
		if ($this->getForm()->getRunnable()->isUserObj($mMixed)) {
			$aArgs[] =& $this;
		}
		$ref = $this->getForm()->getRunnable();
		$mRes = call_user_func_array(array($ref, 'callRunnable'), $aArgs);

		return $mRes;
	}

	function getName() {
		return $this->aObjectType['CLASS'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.mainobject.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.mainobject.php']);
}
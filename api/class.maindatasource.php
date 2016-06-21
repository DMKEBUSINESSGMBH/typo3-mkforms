<?php

abstract class formidable_maindatasource extends formidable_mainobject {

	var $aODataSets = array();

	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
	}

	function _getRecordWindow($iPage, $iRowsPerPage, $bMax = FALSE) {

		$iOffset = ($iPage) * $iRowsPerPage;    // counting the offset
		$iNbDisplayed = $iRowsPerPage;

		if ($bMax !== FALSE) {

			if ($bMax !== FALSE && (($iOffset + $iRowsPerPage) > $bMax)) {
				$iNbDisplayed = $bMax - $iOffset;
			}
		}

		return array(
			"sql" => ($iNbDisplayed != "") ? " LIMIT " . $iOffset . ", " . $iNbDisplayed . " " : "",
			"page" => $iPage,
			"offset" => $iOffset,
			"rowsperpage" => $iRowsPerPage,
			"nbdisplayed" => $iNbDisplayed,
		);
	}

	function _getTotalNumberOfPages($iRowsPerPage, $iNbRows, $iMaximum = FALSE) {

		if ($iMaximum !== FALSE && $iNbRows > $iMaximum) {
			$iNbRows = $iMaximum;
		}

		return ceil($iNbRows / $iRowsPerPage);
	}

	function getName() {
		return $this->_navConf("/name");
	}

	function writable() {
		return $this->_defaultFalse("/writable");
	}

	function initDataSet($sKey) {
		return FALSE;    // abstract
	}

	function dset_decodeSignature($sSignature) {
		if ($sSignature !== FALSE) {
			$sSignature = base64_decode($sSignature);
			$aParts = explode(":", $sSignature);
			if (count($aParts) >= 2) {
				$sTheirLock = $aParts[0];
				array_shift($aParts);
				$sData = implode(":", $aParts);
				if ($sTheirLock === $this->oForm->_getSafeLock($sData)) {
					return $sData;
				}
			}
		}

		return FALSE;
	}

	function dset_mapPath($sSignature, &$oDataBridge, $sAbsName) {

		if (!array_key_exists($sSignature, $this->aODataSets)) {
			return FALSE;
		}

		if ($this->aODataSets[$sSignature]->isFloating()) {
			return $this->dset_mapPathFloating($sSignature, $oDataBridge, $sAbsName);
		} else {
			return $this->dset_mapPathAnchored($sSignature, $oDataBridge, $sAbsName);
		}
	}

	function dset_mapPathFloating($sSignature, &$oDataBridge, $sAbsName) {
		return $this->dset_mapPathAnchored($sSignature, $oDataBridge, $sAbsName);
	}

	function dset_mapPathAnchored($sSignature, &$oDataBridge, $sAbsName) {
		if (array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
			$aData = $this->aODataSets[$sSignature]->getData();
			$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($oDataBridge);
			$sPath = str_replace(".", "/", $sRelName);

			if ($this->dset_hasFlexibleStructure()) {
				# we check if the dataset has a flexible structure
				# like a flexform for instance, because if so, a path that doesn't exists within given dataset may still
				# be set written in the dataset
				return $sPath;
			} elseif ($this->oForm->_navConf($sPath, $aData) === FALSE) {
				# path relative to databridge not found withing given dataset,
				# let's try with the simple name of the renderlet (typically the name of a field in a DB-table)

				$sPath = $this->oForm->aORenderlets[$sAbsName]->getName();
				if ($this->aODataSets[$sSignature]->isFloating()) {
					# structure is floating;
					# there's no data given, so we cannot determine a dataset-structure
					# we know the dataset has a strong structure (not flexible)
					# so our best bet here is to assume that the name of the renderlet
					# corresponds to something in the future dataset structure
					# makes sense as if it doesn't correspond to anything,
					# the developper would have counter-indicated it
					# using the /map property on the renderlet
					# or the /mapping property on the databridge

					return $sPath;
				} else {
					# structure is anchored (not floating);
					# data was given, so we have a structure we can rely on
					# to check if the data is correctly mapped or not

					$aData = $this->aODataSets[$sSignature]->getData();
					if ($this->oForm->_navConf($sPath, $aData) !== FALSE) {
						return $sPath;
					}
				}
			} else {
				return $sPath;
			}
		}

		return FALSE;
	}

	function dset_hasFlexibleStructure() {
		# TRUE if structure may expand / is flexible (like a flexform and unlike db-table)
		return $this->_defaultFalse("/flexiblestructure");
	}

	function dset_alwaysNeedsToBeWritten() {
		return FALSE;
	}

	function baseCleanBeforeSession() {
		$aKeys = array_keys($this->aODataSets);
		reset($aKeys);
		while (list(, $sSignature) = each($aKeys)) {
			$this->aODataSets[$sSignature]->cleanBeforeSession();
			$this->aODataSets[$sSignature] = serialize($this->aODataSets[$sSignature]);
		}
	}

	function awakeInSession(&$oForm) {
		$this->oForm =& $oForm;
		$aKeys = array_keys($this->aODataSets);
		reset($aKeys);
		while (list(, $sSignature) = each($aKeys)) {
			$this->aODataSets[$sSignature] = unserialize($this->aODataSets[$sSignature]);
			$this->aODataSets[$sSignature]->oDataSource =& $this;
			$this->aODataSets[$sSignature]->awakeInSession($this->oForm);
		}
	}

	function dset_writeDataSet($sSignature) {

		if (!array_key_exists($sSignature, $this->aODataSets)) {
			return FALSE;
		}

		if ($this->dset_alwaysNeedsToBeWritten() || $this->aODataSets[$sSignature]->needsToBeWritten()) {
			if ($this->aODataSets[$sSignature]->isFloating()) {
				$this->_setSyncDataFloating(
					$sSignature,
					$this->aODataSets[$sSignature]->getKey(),
					$this->aODataSets[$sSignature]->aData
				);
			} else {
				$this->_setSyncDataAnchored(
					$sSignature,
					$this->aODataSets[$sSignature]->getKey(),
					$this->aODataSets[$sSignature]->aData
				);
			}
		}
	}

	function _setSyncDataFloating($sSignature, $sKey, $aData) {
		return $this->setSyncData($sSignature, $sKey, $aData);
	}

	function _setSyncDataAnchored($sSignature, $sKey, $aData) {
		return $this->setSyncData($sSignature, $sKey, $aData);
	}

	function dset_setCellValue($sSignature, $sPath, $mValue, $sAbsName = FALSE) {
		$this->aODataSets[$sSignature]->setCellValue($sPath, $mValue);
	}

	function getRowNumberForUid($iUid) {
		return FALSE;
	}

	/**
	 * Kindklassen sollten hier die Daten liefern
	 *
	 * @param array $aConfig
	 * @param array $aFilters
	 *
	 * @return array
	 */
	abstract function &_fetchData($aConfig = array(), $aFilters = array());

	public function &fetchData($aConfig = array(), $aFilters = array()) {
		return $this->_fetchData($aConfig, $aFilters);
	}
}

if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/api/class.maindatasource.php"]) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/api/class.maindatasource.php"]);
}

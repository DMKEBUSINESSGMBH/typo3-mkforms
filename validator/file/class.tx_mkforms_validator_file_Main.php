<?php
/**
 * Plugin 'va_file' for the 'ameos_formidable' extension.
 *
 * @author	Luc Muller <typo3dev@ameos.com>
 */


class tx_mkforms_validator_file_Main extends formidable_mainvalidator {

	var $oFileFunc = null; //object for basics file function

	function validate($oRdt) {

		$sAbsName = $oRdt->getAbsName();
		$sFileName = $oRdt->getValue();

		$bError = FALSE;

		$this->oFileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');

		if($oRdt->_getType() === 'FILE') {	// renderlet:FILE
			$sFileName = strtolower($this->oFileFunc->cleanFileName($sFileName));
		} elseif($oRdt->_getType() === 'UPLOAD') {
			// managing multiple, if set

			if($sFileName !== '') {
				if($oRdt->isMultiple()) {
					$aFileList = t3lib_div::trimExplode(',', $sFileName);
					$sFileName = array_pop($aFileList);	// last one, and remove from list; will be added later if valid
				}
			}
		}

		if($sFileName === '') {
			// never evaluate if value is empty
			// as this is left to STANDARD:required
			return '';
		}

		$aKeys = array_keys($this->_navConf('/'));
		reset($aKeys);
		while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {

			// Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
			if(!$this->canValidate($oRdt, $sKey, $sFileName)){
				break;
			}

			/***********************************************************************
			*
			*	/extension
			*
			***********************************************************************/

			if($sKey{0} === 'e' && t3lib_div::isFirstPartOfStr($sKey, 'extension')) {

				$sFileExts = $this->_navConf('/' . $sKey . '/value');
				if($this->oForm->isRunneable($sFileExts)) {
					$sFileExts = $this->callRunneable($sFileExts);
				}

				$aExtensions = t3lib_div::trimExplode(
					',',
					$sFileExts
				);

				if(!$this->_checkFileExt($sFileName, $aExtensions, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'FILE:extension',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
					);

					$bError = TRUE;
					break;
				}
			}





			/***********************************************************************
			*
			*	/filesize
			*
			***********************************************************************/

			if($sKey{0} === 'f' && t3lib_div::isFirstPartOfStr($sKey, 'filesize') && t3lib_div::isFirstPartOfStr($sKey, 'filesizekb') && t3lib_div::isFirstPartOfStr($sKey, 'filesizemb')) {
				$mSize = $this->_navConf('/' . $sKey . '/value');

				if($this->oForm->isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSize($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'FILE:filesize',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
					);

					$bError = TRUE;
					break;
				}
			}




			/***********************************************************************
			*
			*	/filesizekb
			*
			***********************************************************************/

			if($sKey{0} === 'f' && t3lib_div::isFirstPartOfStr($sKey, 'filesizekb')) {
				$mSize = $this->_navConf('/' . $sKey . '/value');

				if($this->oForm->isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSizeKb($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'FILE:filesizekb',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
					);

					$bError = TRUE;
					break;
				}
			}




			/***********************************************************************
			*
			*	/filesizemb
			*
			***********************************************************************/

			if($sKey{0} === 'f' && t3lib_div::isFirstPartOfStr($sKey, 'filesizemb')) {
				$mSize = $this->_navConf('/' . $sKey . '/value');

				if($this->oForm->isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSizeMb($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'FILE:filesizemb',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
					);

					$bError = TRUE;
					break;
				}
			}
		}

		if($bError === TRUE && $oRdt->_getType() === 'UPLOAD') {
			if($oRdt->isMultiple()) {
				// current filenamehas been remove from aFileList by previous array_pop
				$oRdt->setValue(implode(', ', $aFileList));
			} else {
				$oRdt->setValue('');
			}
		}
	}

	function _checkFileExt($sFileName,$aValues, $sAbsName) {

		if(is_array($sFileName)){
			$sFilePath = $sFileName['tmp_name'];
			$sFileName = $sFileName['name'];
		}

		// Wurde keine Datei übertragen, nicht validieren
		if(empty($sFileName)) {
			return TRUE;
		}

		$sExt = array_pop(explode('.',$sFileName));
		$sExt = strtolower(str_replace('.', '', $sExt));

		foreach($aValues as $key=>$val) {
			$val = strtolower(str_replace('.', '', $val));
			if($val === $sExt) {
				return TRUE;
			}
		}

		// no match, unlink
		if(!isset($sFilePath)) $sFilePath = $this->_getFullServerPath($sAbsName, $sFilePath);
		$this->_unlink($sFilePath);
		return FALSE;
	}

	function _checkFileSizeKb($sFileName, $iMaxFileSize, $sAbsName) {
		return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, 'kilobyte');
	}

	function _checkFileSizeMb($sFileName, $iMaxFileSize, $sAbsName) {
		return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, 'megabyte');
	}

	function _checkFileSize($sFileName, $iMaxFileSize, $sAbsName, $sType = 'byte') {

		if(!empty($sFileName)) {

			if(is_array($sFileName)) {
				if($sFileName['error'] == UPLOAD_ERR_INI_SIZE || $sFileName['error'] == UPLOAD_ERR_FORM_SIZE) return FALSE;
				$sFullPath = $sFileName['tmp_name'];
				$iSize = intval($sFileName['size']);
			} else {
				$sFullPath = $this->_getFullServerPath($sAbsName, $sFileName);
				$aInfos = t3lib_basicFileFunctions::getTotalFileInfo($sFullPath);
				$iSize = intval($aInfos['size']);
			}

			switch($sType) {
				case 'kilobyte': {
					$iMaxFileSize = $iMaxFileSize * 1024;
					break;
				}
				case 'megabyte': {
					$iMaxFileSize = $iMaxFileSize * 1024 * 1024;
					break;
				}
			}

			if($iSize <= intval($iMaxFileSize)) {
				return TRUE;
			} else {
				$this->_unlink($sFullPath);
				return FALSE;
			}
		}

		return TRUE;
	}

	function _getFullServerPath($sAbsName, $sFileName) {
		return $this->oForm->aORenderlets[$sAbsName]->getFullServerPath($sFileName);
	}

	function _unlink($sFullPath) {
		@unlink($sFullPath);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/validator/file/class.tx_mkforms_validator_file_Main.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/validator/file/class.tx_mkforms_validator_file_Main.php']);
}
?>

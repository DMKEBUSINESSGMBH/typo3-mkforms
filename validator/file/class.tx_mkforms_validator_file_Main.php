<?php
/**
 * Plugin 'va_file' for the 'ameos_formidable' extension.
 *
 * @author  Luc Muller <typo3dev@ameos.com>
 */

class tx_mkforms_validator_file_Main extends formidable_mainvalidator
{
    public $oFileFunc = null; //object for basics file function

    /**
     * (non-PHPdoc)
     * @see formidable_mainvalidator::validate()
     */
    public function validate(&$oRdt)
    {
        $sAbsName = $oRdt->getAbsName();
        // Der Rückgabewert kann drei Zustände annehmen:
        // - Bei normalen Upload-Felder ist es ein String oder leer
        // - beim MEDIAUPLOAD ist es ein Array oder leer oder ein Integer
        // - bei sonstigen Widgets ein String
        $sFileName = $oRdt->getValue();

        $bError = false;

        $this->oFileFunc = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getBasicFileUtilityClass());

        if ($oRdt->_getType() === 'FILE') {    // renderlet:FILE
            $sFileName = strtolower($this->oFileFunc->cleanFileName($sFileName));
        } elseif ($oRdt->_getType() === 'UPLOAD') {
            // managing multiple, if set

            if ($sFileName !== '') {
                if ($oRdt->isMultiple()) {
                    $aFileList = Tx_Rnbase_Utility_Strings::trimExplode(',', $sFileName);
                    $sFileName = array_pop($aFileList);    // last one, and remove from list; will be added later if valid
                }
            }
        } elseif ($oRdt->_getType() === 'MEDIAUPLOAD' && tx_rnbase_util_Math::isInteger($sFileName)) {
            // Wenn das Upload-Widget einen Integer liefert, dann ist das nur die Anzahl der Referenzen
            // In dem Fall hat kein Upload stattgefunden. Es gibt also nichts zu validieren.
            $sFileName = '';
        }

        if ($sFileName === '') {
            // never evaluate if value is empty
            // as this is left to STANDARD:required
            return '';
        }

        $aKeys = array_keys($this->_navConf('/'));
        reset($aKeys);
        foreach ($aKeys as $sKey) {
            // Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
            if ($oRdt->hasError() || !$this->canValidate($oRdt, $sKey, $sFileName)) {
                break;
            }

            /***********************************************************************
            *
            *	/extension
            *
            ***********************************************************************/

            if ($sKey{0} === 'e' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'extension')) {
                $sFileExts = $this->_navConf('/' . $sKey . '/value');
                if ($this->oForm->isRunneable($sFileExts)) {
                    $sFileExts = $this->callRunneable($sFileExts);
                }

                $aExtensions = Tx_Rnbase_Utility_Strings::trimExplode(
                    ',',
                    $sFileExts
                );

                if (!$this->_checkFileExt($sFileName, $aExtensions, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:extension',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
                    );

                    $bError = true;
                    break;
                }
            }





            /***********************************************************************
            *
            *	/filesize
            *
            ***********************************************************************/

            if ($sKey{0} === 'f' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'filesize') &&
                    Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'filesizekb') &&
                    Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'filesizemb')) {
                $mSize = $this->_navConf('/' . $sKey . '/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSize($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesize',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
                    );

                    $bError = true;
                    break;
                }
            }




            /***********************************************************************
            *
            *	/filesizekb
            *
            ***********************************************************************/

            if ($sKey{0} === 'f' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'filesizekb')) {
                $mSize = $this->_navConf('/' . $sKey . '/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSizeKb($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesizekb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
                    );

                    $bError = true;
                    break;
                }
            }




            /***********************************************************************
            *
            *	/filesizemb
            *
            ***********************************************************************/

            if ($sKey{0} === 'f' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'filesizemb')) {
                $mSize = $this->_navConf('/' . $sKey . '/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSizeMb($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesizemb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message'))
                    );

                    $bError = true;
                    break;
                }
            }
        }

        if ($bError === true && $oRdt->_getType() === 'UPLOAD') {
            if ($oRdt->isMultiple()) {
                // current filenamehas been remove from aFileList by previous array_pop
                $oRdt->setValue(implode(', ', $aFileList));
            } else {
                $oRdt->setValue('');
            }
        }
    }

    public function _checkFileExt($sFileName, $aValues, $sAbsName)
    {
        if (is_array($sFileName)) {
            $sFilePath = $sFileName['tmp_name'];
            $sFileName = $sFileName['name'];
        }

        // Wurde keine Datei übertragen, nicht validieren
        if (empty($sFileName)) {
            return true;
        }

        $sExt = array_pop(explode('.', $sFileName));
        $sExt = strtolower(str_replace('.', '', $sExt));

        foreach ($aValues as $key => $val) {
            $val = strtolower(str_replace('.', '', $val));
            if ($val === $sExt) {
                return true;
            }
        }

        // no match, unlink
        if (!isset($sFilePath)) {
            $sFilePath = $this->_getFullServerPath($sAbsName, $sFileName);
        }
        $this->_unlink($sFilePath);

        return false;
    }

    public function _checkFileSizeKb($sFileName, $iMaxFileSize, $sAbsName)
    {
        return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, 'kilobyte');
    }

    public function _checkFileSizeMb($sFileName, $iMaxFileSize, $sAbsName)
    {
        return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, 'megabyte');
    }

    public function _checkFileSize($sFileName, $iMaxFileSize, $sAbsName, $sType = 'byte')
    {
        if (!empty($sFileName)) {
            if (is_array($sFileName)) {
                if ($sFileName['error'] == UPLOAD_ERR_INI_SIZE || $sFileName['error'] == UPLOAD_ERR_FORM_SIZE) {
                    return false;
                }
                $sFullPath = $sFileName['tmp_name'];
                $iSize = (int)$sFileName['size'];
            } else {
                $sFullPath = $this->_getFullServerPath($sAbsName, $sFileName);
                $iSize = (int)@filesize($sFullPath);
            }

            switch ($sType) {
                case 'kilobyte': {
                    $iMaxFileSize = $iMaxFileSize * 1024;
                    break;
                }
                case 'megabyte': {
                    $iMaxFileSize = $iMaxFileSize * 1024 * 1024;
                    break;
                }
            }

            if ($iSize <= (int)$iMaxFileSize) {
                return true;
            } else {
                $this->_unlink($sFullPath);

                return false;
            }
        }

        return true;
    }

    public function _getFullServerPath($sAbsName, $sFileName)
    {
        return $this->oForm->aORenderlets[$sAbsName]->getFullServerPath($sFileName);
    }

    public function _unlink($sFullPath)
    {
        @unlink($sFullPath);
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/file/class.tx_mkforms_validator_file_Main.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/file/class.tx_mkforms_validator_file_Main.php']);
}

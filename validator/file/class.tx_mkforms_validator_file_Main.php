<?php
/**
 * Plugin 'va_file' for the 'ameos_formidable' extension.
 *
 * @author  Luc Muller <typo3dev@ameos.com>
 */
class tx_mkforms_validator_file_Main extends formidable_mainvalidator
{
    public $oFileFunc; // object for basics file function

    /**
     * (non-PHPdoc).
     *
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

        $this->oFileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getBasicFileUtilityClass());

        if ('FILE' === $oRdt->_getType()) {    // renderlet:FILE
            $sFileName = strtolower($this->oFileFunc->cleanFileName($sFileName));
        } elseif ('UPLOAD' === $oRdt->_getType()) {
            // managing multiple, if set

            if ('' !== $sFileName) {
                if ($oRdt->isMultiple()) {
                    $aFileList = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sFileName);
                    $sFileName = array_pop($aFileList);    // last one, and remove from list; will be added later if valid
                }
            }
        } elseif ('MEDIAUPLOAD' === $oRdt->_getType() && \Sys25\RnBase\Utility\Math::isInteger($sFileName)) {
            // Wenn das Upload-Widget einen Integer liefert, dann ist das nur die Anzahl der Referenzen
            // In dem Fall hat kein Upload stattgefunden. Es gibt also nichts zu validieren.
            $sFileName = '';
        }

        if ('' === $sFileName) {
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

            if ('e' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'extension')) {
                $sFileExts = $this->_navConf('/'.$sKey.'/value');
                if ($this->oForm->isRunneable($sFileExts)) {
                    $sFileExts = $this->callRunneable($sFileExts);
                }

                $aExtensions = \Sys25\RnBase\Utility\Strings::trimExplode(
                    ',',
                    $sFileExts
                );

                if (!$this->_checkFileExt($sFileName, $aExtensions, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:extension',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
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

            if ('f' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'filesize')
                    && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'filesizekb')
                    && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'filesizemb')) {
                $mSize = $this->_navConf('/'.$sKey.'/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSize($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesize',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
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

            if ('f' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'filesizekb')) {
                $mSize = $this->_navConf('/'.$sKey.'/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSizeKb($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesizekb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
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

            if ('f' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'filesizemb')) {
                $mSize = $this->_navConf('/'.$sKey.'/value');

                if ($this->oForm->isRunneable($mSize)) {
                    $mSize = $this->callRunneable($mSize);
                }

                if (!$this->_checkFileSizeMb($sFileName, $mSize, $sAbsName)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'FILE:filesizemb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    $bError = true;
                    break;
                }
            }
        }

        if (true === $bError && 'UPLOAD' === $oRdt->_getType()) {
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
                if (UPLOAD_ERR_INI_SIZE == $sFileName['error'] || UPLOAD_ERR_FORM_SIZE == $sFileName['error']) {
                    return false;
                }
                $sFullPath = $sFileName['tmp_name'];
                $iSize = (int) $sFileName['size'];
            } else {
                $sFullPath = $this->_getFullServerPath($sAbsName, $sFileName);
                $iSize = (int) @filesize($sFullPath);
            }

            switch ($sType) {
                case 'kilobyte':
                    $iMaxFileSize = $iMaxFileSize * 1024;
                    break;

                case 'megabyte':
                    $iMaxFileSize = $iMaxFileSize * 1024 * 1024;
                    break;
            }

            if ($iSize <= (int) $iMaxFileSize) {
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

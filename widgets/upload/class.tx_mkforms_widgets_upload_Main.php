<?php
/**
 * Plugin 'rdt_upload' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_upload_Main extends formidable_mainrenderlet
{
    public $bArrayValue = true;
    public $aUploaded = false;    // array if file has just been uploaded
    public $bUseDam;    // will be set to TRUE or FALSE, depending on /dam/use=boolean, default FALSE

    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
        $this->aEmptyStatics['targetdir'] = AMEOSFORMIDABLE_VALUE_NOT_SET;
        $this->aEmptyStatics['targetfile'] = AMEOSFORMIDABLE_VALUE_NOT_SET;
        $this->aStatics['targetdir'] = $this->aEmptyStatics['targetdir'];
        $this->aStatics['targetfile'] = $this->aEmptyStatics['targetfile'];
    }

    public function cleanStatics()
    {
        parent::cleanStatics();
        unset($this->aStatics['targetdir']);
        unset($this->aStatics['targetfile']);
        $this->aStatics['targetdir'] = $this->aEmptyStatics['targetdir'];
        $this->aStatics['targetfile'] = $this->aEmptyStatics['targetfile'];
    }

    public function checkPoint(&$aPoints, array &$options = [])
    {
        parent::checkPoint($aPoints, $options);

        if (in_array('after-init-datahandler', $aPoints)) {
            $this->manageFile();
        }
    }

    public function justBeenUploaded()
    {
        if (false !== $this->aUploaded) {
            reset($this->aUploaded);

            return $this->aUploaded;
        }

        return false;
    }

    public function _render()
    {
        if ((false === $this->_navConf('/data/targetdir')) && (false === $this->_navConf('/data/targetfile'))) {
            $this->oForm->mayday('renderlet:UPLOAD[name='.$this->_getName().'] You have to provide either <b>/data/targetDir</b> or <b>/data/targetFile</b> for renderlet:UPLOAD to work properly.');
        }

        $sValue = $this->getValue();

        // @FIXME manchmal steckt ein array im value, was nie passieren darf!!!
        // @see tx_mkforms_widgets_damupload_Main::_render() Zeile 47
        // evtl. hilt dieser bugfix auch
        if (!is_string($sValue)) {
            $sValue = 0;
        }

        $sInput = '<input type="file" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().' />';
        $sInput .= '<input type="hidden" name="'.$this->_getElementHtmlName().'[backup]" id="'.$this->_getElementHtmlId().'_backup" value="'.$this->getValueForHtml($sValue).'" />';
        $sValuePreview = $sValueCvs = $sLinkCvs = $sLis = $sLinkLis = '';

        if (!empty($sValue) && $this->defaultTrue('showfilelist')) {
            $aValues = \Sys25\RnBase\Utility\Strings::trimExplode(',', $this->getValueForHtml($sValue));

            foreach ($aValues as $url) {
                $sWebPath = tx_mkforms_util_Div::toWebPath(
                    $this->getServerPath($url)
                );
                $aLinks[] = '<a href="'.$sWebPath.'" target="_blank">'.$url.'</a>';
            }

            $sLis = '<li>'.implode('</li><li>', $aValues).'</li>';
            $sLinkLis = '<li>'.implode('</li><li>', $aLinks).'</li>';
            $sValueCvs = implode(', ', $aValues);
            $sLinkCvs = implode(', ', $aLinks);

            if (('' !== trim($sValue)) && (true === $this->defaultTrue('showlink'))) {
                if ('' !== trim($sLinkCvs)) {
                    $sValuePreview = $sLinkCvs.'<br />';
                }
            } else {
                if ('' !== trim($sValueCvs)) {
                    $sValuePreview = $sValueCvs.'<br />';
                }
            }
        }

        $aRes = [
            '__compiled' => $this->_displayLabel($this->getLabel()).$sValuePreview.$sInput,
            'input' => $sInput,
            'filelist.' => [
                'csv' => $sValueCvs,
                'ol' => '<ol>'.$sLis.'</ol>',
                'ul' => '<ul>'.$sLis.'</ul>',
            ],
            'linklist.' => [
                'csv' => $sLinkCvs,
                'ol' => '<ol>'.$sLinkLis.'</ol>',
                'ul' => '<ul>'.$sLinkLis.'</ul>',
            ],
            'value' => $sValue,
            'value.' => [
                'preview' => $sValuePreview,
            ],
        ];

        if (!$this->isMultiple()) {
            if ('' != trim($sValue)) {
                $aRes['file.']['webpath'] = tx_mkforms_util_Div::toWebPath($this->getServerPath());
            } else {
                $aRes['file.']['webpath'] = '';
            }
        }

        return $aRes;
    }

    public function getServerPath($sFileName = false)
    {
        if (false !== ($sTargetFile = $this->getTargetFile())) {
            return $sTargetFile;
        } elseif (false !== $sFileName) {
            return $this->getTargetDir().$sFileName;
        }

        return $this->getTargetDir().$this->getValue();
    }

    public function getFullServerPath($sFileName = false)
    {
        // dummy method for compat with renderlet:FILE and validator:FILE
        return $this->getServerPath($sFileName);
    }

    public function manageFile()
    {
        /*
            ALGORITHM of file management

            0: PAGE DISPLAY:
                1: file has been uploaded
                    1.1: moving file to targetdir and setValue(newfilename)
                        1.1.1: multiple == TRUE
                            1.1.1.1: data *are not* stored in database (creation mode)
                                1.1.1.1.1: setValue to backupdata . newfilename
                            1.1.1.2: data *are* stored in database
                                1.1.1.2.1: setValue to storeddata . newfilename
                2: file has not been uploaded
                    2.1: data *are not* stored in database, as it's a creation mode not fully completed yet
                        2.1.1: formdata is a string
                            2.1.1.1: formdata != backupdata ( value has been set by some server event with setValue )
                                2.1.1.1.1: no need to setValue as it already contains what we need
                            2.2.1.2: formdata == backupdata
                                2.2.1.2.1: setValue to backupdata
                        2.1.2: formdata is an array, and error!=0 (form submitted but no file given)
                            2.1.2.1: setValue to backupdata
                    2.2: data *are* stored in database
                            2.2.1: formdata is a string
                                2.2.1.1: formdata != storeddata ( value has been set by some server event with setValue )
                                    2.2.1.1.1: no need to setValue as it already contains what we need
                                2.2.1.2: formdata == storeddata
                                    2.2.1.2.1: setValue to storeddata
                            2.2.2: formdata is an array, and error!=0 ( form submitted but no file given)
                                2.2.2.1: setValue to storeddata
        */

        $aData = $this->getValue();

        if (is_array($aData) && isset($aData['error']) && 0 == $aData['error']) {
            // a file has just been uploaded

            if (false !== ($sTargetFile = $this->getTargetFile())) {
                $sTargetDir = \Sys25\RnBase\Utility\T3General::dirname($sTargetFile);
                $sName = basename($sTargetFile);
                $sTarget = $sTargetFile;
            } else {
                $sTargetDir = $this->getTargetDir();

                $sName = basename($aData['name']);
                if ($this->defaultTrue('/data/cleanfilename') && $this->defaultTrue('/cleanfilename')) {
                    $sName = tx_mkforms_util_Div::cleanupFileName($sName);
                }

                $sTarget = $sTargetDir.$sName;

                if (!file_exists($sTargetDir)) {
                    if (true === $this->defaultFalse('/data/targetdir/createifneeded')) {
                        // the target does not exist, we have to create it
                        tx_mkforms_util_Div::mkdirDeepAbs($sTargetDir);
                    }
                }

                if (!$this->oForm->_defaultFalse('/data/overwrite', $this->aElement)) {
                    // rename the file if same name already exists

                    $sExt = ((false === strpos($sName, '.')) ? '' : '.'.substr(strrchr($sName, '.'), 1));

                    for ($i = 1; file_exists($sTarget); ++$i) {
                        $sTarget = $sTargetDir.substr($sName, 0, strlen($sName) - strlen($sExt)).'['.$i.']'.$sExt;
                    }

                    $sName = basename($sTarget);
                }
            }

            if (move_uploaded_file($aData['tmp_name'], $sTarget)) {
                // success
                $this->aUploaded = [
                    'dir' => $sTargetDir,
                    'name' => $sName,
                    'path' => $sTarget,
                    'infos' => $aData,
                ];

                $sCurFile = $sName;

                if ($this->isMultiple()) {
                    // csv string of file names

                    if (false === $this->oForm->oDataHandler->_edition() || $this->_renderOnly()) {
                        $sCurrent = $aData['backup'];
                    } else {
                        $sCurrent = trim($this->oForm->oDataHandler->_getStoredData($this->_getName()));
                    }

                    if ('' !== $sCurrent) {
                        $aCurrent = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sCurrent);
                        if (!in_array($sCurFile, $aCurrent)) {
                            $aCurrent[] = $sCurFile;
                        }

                        // adding filename to list
                        $this->setValue(implode(',', $aCurrent));
                    } else {
                        // first value in multiple list
                        $this->setValue($sCurFile);
                    }
                } else {
                    // replacing value in list
                    $this->setValue($sCurFile);
                }
            }
        } else {
            $aStoredData = $this->oForm->oDataHandler->_getStoredData();

            if ((false === $this->oForm->oDataHandler->_edition()) || (!array_key_exists($this->_getName(), $aStoredData))) {
                if (is_string($aData)) {
                    if (true === $this->bForcedValue) {
                        // value has been set by some process (probably a server event) with setValue()
                        /* nothing to do, so */
                    } else {
                        // persisting existing value
                        $this->setValue(
                            $aData
                        );
                    }
                } else {
                    // it's an array, and error!=0
                    // persisting existing value
                    $sBackup = '';
                    if (is_array($aData) && array_key_exists('backup', $aData)) {
                        $sBackup = $aData['backup'];
                    }

                    $this->setValue($sBackup);
                }
            } else {
                $sStoredData = $aStoredData[$this->_getName()];
                // @see tx_mkforms_widgets_damupload_Main::_render
                // da gab es auch mal ein Problem und ein Bugfix
                if (!is_string($sStoredData)) {
                    \Sys25\RnBase\Utility\Logger::fatal(
                        'Der value des Uploadfelds ist kein string, was nie passieren darf!',
                        'mkforms',
                        [
                            'widget' => $this->_getName(),
                            '$aStoredData' => $aStoredData,
                            '$sStoredData' => $sStoredData,
                            '$aData' => $aData,
                            'getValue' => $this->getValue(),
                            'Validierungsfehler' => $this->getForm()->_aValidationErrors,
                            '$GET' => \Sys25\RnBase\Utility\T3General::_GET(),
                            '$POST' => \Sys25\RnBase\Utility\T3General::_POST(),
                        ]
                    );
                }

                if (is_string($aData)) {
                    // $aData is a string

                    if (trim($aData) !== $sStoredData) {
                        // value has been set by some process (probably a server event) with setValue()
                        /* nothing to do, so */
                    } else {
                        // persisting existing value
                        $this->setValue($sStoredData);
                    }
                } else {
                    // it's an array, and error!=0
                    // persisting existing value
                    $this->setValue($sStoredData);
                }
            }
        }
    }

    public function getTargetDir()
    {
        if (AMEOSFORMIDABLE_VALUE_NOT_SET === $this->aStatics['targetdir']) {
            $this->aStatics['targetdir'] = false;
            if (false !== ($mTargetDir = $this->_navConf('/data/targetdir'))) {
                if ($this->oForm->isRunneable($mTargetDir)) {
                    $mTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetDir);
                }
                if (is_array($mTargetDir) && !empty($mTargetDir)) {
                    $mTargetDir = $mTargetDir['__value'];
                }
                if (is_string($mTargetDir) && '' !== trim($mTargetDir)) {
                    if (tx_mkforms_util_Div::isAbsPath($mTargetDir)) {
                        $this->aStatics['targetdir'] = tx_mkforms_util_Div::removeEndingSlash($mTargetDir).'/';
                    } else {
                        $this->aStatics['targetdir'] = tx_mkforms_util_Div::removeEndingSlash(
                            tx_mkforms_util_Div::toServerPath($mTargetDir)
                        ).'/';
                    }
                }
            }
        }

        return $this->aStatics['targetdir'];
    }

    public function getTargetFile()
    {
        if (false !== ($mTargetFile = $this->_navConf('/data/targetfile'))) {
            if ($this->oForm->isRunneable($mTargetFile)) {
                $mTargetFile = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetFile);
            }

            if (is_string($mTargetFile) && '' !== trim($mTargetFile)) {
                return $this->oForm->toServerPath(trim($mTargetFile));
            }
        }

        return false;
    }

    public function isMultiple()
    {
        return $this->oForm->_defaultFalse('/data/multiple', $this->aElement);
    }

    public function deleteFile($sFile)
    {
        $aValues = \Sys25\RnBase\Utility\Strings::trimExplode(',', $this->getValue());
        unset($aValues[array_search($sFile, $aValues)]);
        @unlink($this->getFullServerPath($sFile));
        $this->setValue(implode(',', $aValues));
    }

    public function useDam()
    {
        if (is_null($this->bUseDam)) {
            if (true === $this->oForm->_defaultFalse('/dam/use', $this->aElement)) {
                if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam')) {
                    $this->oForm->mayday('renderlet:UPLOAD[name='.$this->_getName()."], can't connect to <b>DAM</b>: <b>EXT:dam is not loaded</b>.");
                }

                $this->bUseDam = true;
                require_once PATH_txdam.'lib/class.tx_dam.php';
            } else {
                $this->bUseDam = false;
            }
        }

        return $this->bUseDam;
    }
}

<?php
/**
 * Plugin 'rdt_upload' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */

tx_rnbase::load('tx_mkforms_util_Div');

class tx_mkforms_widgets_upload_Main extends formidable_mainrenderlet
{
    public $bArrayValue = true;
    public $aUploaded = false;    // array if file has just been uploaded
    public $bUseDam = null;    // will be set to TRUE or FALSE, depending on /dam/use=boolean, default FALSE

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

    public function checkPoint(&$aPoints, array &$options = array())
    {
        parent::checkPoint($aPoints, $options);

        if (in_array('after-init-datahandler', $aPoints)) {
            $this->manageFile();
        }
    }

    public function justBeenUploaded()
    {
        if ($this->aUploaded !== false) {
            reset($this->aUploaded);

            return $this->aUploaded;
        }

        return false;
    }

    public function _render()
    {
        if (($this->_navConf('/data/targetdir') === false) && ($this->_navConf('/data/targetfile') === false)) {
            $this->oForm->mayday('renderlet:UPLOAD[name=' . $this->_getName() . '] You have to provide either <b>/data/targetDir</b> or <b>/data/targetFile</b> for renderlet:UPLOAD to work properly.');
        }

        $sValue = $this->getValue();

        //@FIXME manchmal steckt ein array im value, was nie passieren darf!!!
        //@see tx_mkforms_widgets_damupload_Main::_render() Zeile 47
        //evtl. hilt dieser bugfix auch
        if (!is_string($sValue)) {
            $sValue = 0;
        }

        $sInput = '<input type="file" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' />';
        $sInput .= '<input type="hidden" name="' . $this->_getElementHtmlName() . '[backup]" id="' . $this->_getElementHtmlId() . '_backup" value="' . $this->getValueForHtml($sValue) . '" />';

        if (!empty($sValue) && $this->defaultTrue('showfilelist')) {
            $aValues = Tx_Rnbase_Utility_Strings::trimExplode(',', $this->getValueForHtml($sValue));

            foreach ($aValues as $url) {
                $sWebPath = tx_mkforms_util_Div::toWebPath(
                    $this->getServerPath($url)
                );
                $aLinks[] = '<a href="' . $sWebPath . '" target="_blank">' . $url . '</a>';
            }

            $sLis = '<li>' . implode('</li><li>', $aValues) . '</li>';
            $sLinkLis = '<li>' . implode('</li><li>', $aLinks) . '</li>';
            $sValueCvs = implode(', ', $aValues);
            $sLinkCvs = implode(', ', $aLinks);

            $sValuePreview = '';


            if ((trim($sValue) !== '') && ($this->defaultTrue('showlink') === true)) {
                if (trim($sLinkCvs) !== '') {
                    $sValuePreview = $sLinkCvs . '<br />';
                }
            } else {
                if (trim($sValueCvs) !== '') {
                    $sValuePreview = $sValueCvs . '<br />';
                }
            }
        }

        $aRes = array(
            '__compiled' => $this->_displayLabel($this->getLabel()) . $sValuePreview . $sInput,
            'input' => $sInput,
            'filelist.' => array(
                'csv' => $sValueCvs,
                'ol' => '<ol>' . $sLis . '</ol>',
                'ul' => '<ul>' . $sLis . '</ul>',
            ),
            'linklist.' => array(
                'csv' => $sLinkCvs,
                'ol' => '<ol>' . $sLinkLis . '</ol>',
                'ul' => '<ul>' . $sLinkLis . '</ul>',
            ),
            'value' => $sValue,
            'value.' => array(
                'preview' => $sValuePreview,
            ),
        );

        if (!$this->isMultiple()) {
            if (trim($sValue) != '') {
                $aRes['file.']['webpath'] = tx_mkforms_util_Div::toWebPath($this->getServerPath());
            } else {
                $aRes['file.']['webpath'] = '';
            }
        }

        return $aRes;
    }

    public function getServerPath($sFileName = false)
    {
        if (($sTargetFile = $this->getTargetFile()) !== false) {
            return $sTargetFile;
        } elseif ($sFileName !== false) {
            return $this->getTargetDir() . $sFileName;
        }

        return $this->getTargetDir() . $this->getValue();
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

        if (is_array($aData) && isset($aData['error']) && $aData['error'] == 0) {
            // a file has just been uploaded

            if (($sTargetFile = $this->getTargetFile()) !== false) {
                $sTargetDir = Tx_Rnbase_Utility_T3General::dirname($sTargetFile);
                $sName = basename($sTargetFile);
                $sTarget = $sTargetFile;
            } else {
                $sTargetDir = $this->getTargetDir();

                $sName = basename($aData['name']);
                if ($this->defaultTrue('/data/cleanfilename') && $this->defaultTrue('/cleanfilename')) {
                    $sName = tx_mkforms_util_Div::cleanupFileName($sName);
                }

                $sTarget = $sTargetDir . $sName;

                if (!file_exists($sTargetDir)) {
                    if ($this->defaultFalse('/data/targetdir/createifneeded') === true) {
                        // the target does not exist, we have to create it
                        tx_mkforms_util_Div::mkdirDeepAbs($sTargetDir);
                    }
                }


                if (!$this->oForm->_defaultFalse('/data/overwrite', $this->aElement)) {
                    // rename the file if same name already exists

                    $sExt = ((strpos($sName, '.') === false) ? '' : '.' . substr(strrchr($sName, '.'), 1));

                    for ($i = 1; file_exists($sTarget); $i++) {
                        $sTarget = $sTargetDir . substr($sName, 0, strlen($sName) - strlen($sExt)).'['.$i.']'.$sExt;
                    }

                    $sName = basename($sTarget);
                }
            }

            if (move_uploaded_file($aData['tmp_name'], $sTarget)) {
                // success
                $this->aUploaded = array(
                    'dir' => $sTargetDir,
                    'name' => $sName,
                    'path' => $sTarget,
                    'infos' => $aData,
                );

                $sCurFile = $sName;


                if ($this->isMultiple()) {
                    // csv string of file names

                    if ($this->oForm->oDataHandler->_edition() === false || $this->_renderOnly()) {
                        $sCurrent = $aData['backup'];
                    } else {
                        $sCurrent = trim($this->oForm->oDataHandler->_getStoredData($this->_getName()));
                    }

                    if ($sCurrent !== '') {
                        $aCurrent = Tx_Rnbase_Utility_Strings::trimExplode(',', $sCurrent);
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

            $this->handleDam();
        } else {
            $aStoredData = $this->oForm->oDataHandler->_getStoredData();

            if (($this->oForm->oDataHandler->_edition() === false) || (!array_key_exists($this->_getName(), $aStoredData))) {
                if (is_string($aData)) {
                    if ($this->bForcedValue === true) {
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
                //@see tx_mkforms_widgets_damupload_Main::_render
                //da gab es auch mal ein Problem und ein Bugfix
                if (!is_string($sStoredData)) {
                    tx_rnbase::load('tx_rnbase_util_Logger');
                    tx_rnbase_util_Logger::fatal(
                        'Der value des Uploadfelds ist kein string, was nie passieren darf!',
                        'mkforms',
                        array(
                            'widget'                => $this->_getName(),
                            '$aStoredData'            => $aStoredData,
                            '$sStoredData'            => $sStoredData,
                            '$aData'                => $aData,
                            'getValue'                => $this->getValue(),
                            'Validierungsfehler'    => $this->getForm()->_aValidationErrors,
                            '$GET'                    => Tx_Rnbase_Utility_T3General::_GET(),
                            '$POST'                    => Tx_Rnbase_Utility_T3General::_POST(),
                        )
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
        if ($this->aStatics['targetdir'] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
            $this->aStatics['targetdir'] = false;
            if (($mTargetDir = $this->_navConf('/data/targetdir')) !== false) {
                if ($this->oForm->isRunneable($mTargetDir)) {
                    $mTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetDir);
                }
                if (is_array($mTargetDir) && !empty($mTargetDir)) {
                    $mTargetDir = $mTargetDir['__value'];
                }
                if (is_string($mTargetDir) && trim($mTargetDir) !== '') {
                    if (tx_mkforms_util_Div::isAbsPath($mTargetDir)) {
                        $this->aStatics['targetdir'] = tx_mkforms_util_Div::removeEndingSlash($mTargetDir) . '/';
                    } else {
                        $this->aStatics['targetdir'] = tx_mkforms_util_Div::removeEndingSlash(
                            tx_mkforms_util_Div::toServerPath($mTargetDir)
                        ) . '/';
                    }
                }
            }
        }

        return $this->aStatics['targetdir'];
    }

    public function getTargetFile()
    {
        if (($mTargetFile = $this->_navConf('/data/targetfile')) !== false) {
            if ($this->oForm->isRunneable($mTargetFile)) {
                $mTargetFile = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetFile);
            }

            if (is_string($mTargetFile) && trim($mTargetFile) !== '') {
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
        $aValues = Tx_Rnbase_Utility_Strings::trimExplode(',', $this->getValue());
        unset($aValues[array_search($sFile, $aValues)]);
        @unlink($this->getFullServerPath($sFile));
        $this->setValue(implode(',', $aValues));
    }

    public function useDam()
    {
        if (is_null($this->bUseDam)) {
            if ($this->oForm->_defaultFalse('/dam/use', $this->aElement) === true) {
                if (!tx_rnbase_util_Extensions::isLoaded('dam')) {
                    $this->oForm->mayday('renderlet:UPLOAD[name=' . $this->_getName() . "], can't connect to <b>DAM</b>: <b>EXT:dam is not loaded</b>.");
                }

                $this->bUseDam = true;
                require_once(PATH_txdam . 'lib/class.tx_dam.php');
            } else {
                $this->bUseDam = false;
            }
        }

        return $this->bUseDam;
    }

    public function handleDam()
    {
        if ($this->useDam()) {
            if ($this->isMultiple()) {
                $aFiles = Tx_Rnbase_Utility_Strings::trimExplode(',', $this->getValue());
            } else {
                $aFiles = array($this->getValue());
            }

            foreach ($aFiles as $sFileName) {
                $sFilePath = $this->getServerPath($sFileName);

                tx_dam::notify_fileChanged($sFilePath);
                $oMedia = tx_dam::media_getForFile($sFilePath);

                if (($mCategories = $this->_navConf('/dam/addcategories')) !== false) {
                    require_once(PATH_txdam . 'lib/class.tx_dam_db.php');

                    $aCurCats = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                        'uid_foreign',
                        'tx_dam_mm_cat',
                        'uid_local=\'' . $oMedia->meta['uid'] . '\'',
                        '',
                        'sorting ASC'
                    );

                    if (!is_array($aCurCats)) {
                        $aCurCats = array();
                    }

                    foreach ($aCurCats as &$category) {
                        $category = $category['uid_foreign'];
                    }

                    if ($this->oForm->isRunneable($mCategories)) {
                        $mCategories = $this->getForm()->getRunnable()->callRunnableWidget(
                            $this,
                            $mCategories,
                            array(
                                'filename' => $sFileName,
                                'filepath' => $sFilePath,
                                'media' => $oMedia,
                                'currentcats' => $aCurCats,
                                'files' => $aFiles,
                            )
                        );
                    }

                    $aCategories = array();

                    if (!is_array($mCategories)) {
                        if (trim($mCategories) !== '') {
                            $aCategories = Tx_Rnbase_Utility_Strings::trimExplode(',', trim($mCategories));
                        }
                    } else {
                        $aCategories = $mCategories;
                    }

                    if (count($aCategories) > 0) {
                        reset($aCurCats);
                        $aCategories = array_unique(
                            array_merge($aCurCats, $aCategories)
                        );

                        $oMedia->meta['category'] = implode(',', $aCategories);
                        tx_dam_db::insertUpdateData($oMedia->meta);
                    }
                }
            }
        }
    }

    public function damify($sAbsPath)
    {
        if ($this->useDam()) {
            global $PAGES_TYPES;
            if (!isset($PAGES_TYPES)) {
            }

            $bSimulatedUser = false;
            global $BE_USER;

            // Simulate a be user to allow DAM to write in DB
                // see http://lists.typo3.org/pipermail/typo3-project-dam/2009-October/002751.html
                // and http://lists.netfielders.de/pipermail/typo3-project-dam/2006-August/000481.html

            if (!isset($BE_USER) || !is_object($BE_USER) || (int)$GLOBALS['BE_USER']->user['uid'] === 0) {
                // no be_user available
                    // we are using the one created for formidable+dam, named _formidable+dam

                $rSql = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'uid',
                    'be_users',
                    'LOWER(username)=\'_formidable+dam\''    // no enableFields, as this user may should disabled for security reasons
                );

                if (($aRs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rSql)) !== false) {
                    // we found user _formidable+dam
                        // simulating user
                    unset($BE_USER);
                    $BE_USER = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getBackendUserAuthenticationClass());
                    if (property_exists($BE_USER, 'OS')) {
                        $BE_USER->OS = TYPO3_OS;
                    }
                    $BE_USER->setBeUserByUid($aRs['uid']);
                    $BE_USER->fetchGroupData();
                    $BE_USER->backendSetUC();

                    $GLOBALS['BE_USER'] = $BE_USER;
                    $bSimulatedUser = true;
                } else {
                    $this->oForm->mayday('renderlet:UPLOAD[name=' . $this->_getName() . "] /dam/use is enabled; for DAM to operate properly, you have to create a backend-user named '_formidable+dam' with permissions on dam tables");
                }
            }


            tx_dam::notify_fileChanged($sAbsPath);
                // previous line don't work anymore for some obscure reason.
                    // Error seems to be in tx_dam::index_autoProcess()
                    // at line 1332 while checking config value of setup.indexing.auto
                    // EDIT: works now, http://lists.typo3.org/pipermail/typo3-project-dam/2009-October/002749.html


            if ($bSimulatedUser === true) {
                unset($BE_USER);
                unset($GLOBALS['BE_USER']);
            }

            $oMedia = tx_dam::media_getForFile($sAbsPath);

            return $oMedia->meta['uid'];
        }

        return basename($sAbsPath);
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/upload/class.tx_rdtupload.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/upload/class.tx_rdtupload.php']);
}

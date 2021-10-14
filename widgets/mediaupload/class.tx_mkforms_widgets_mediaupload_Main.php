<?php
/**
 * Plugin 'rdt_mediaupload' for the 'mkforms' extension.
 * Based on original rdt_upload from Jerome Schneider <typo3dev@ameos.com>.
 *
 * @author  René Nitzsche <rene@system25.de>
 * @author  Michael Wagner <michael.wagner@dmk-ebusiness.de>
 */
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam')) {
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dam', 'lib/class.tx_dam.php');
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dam', 'lib/class.tx_dam_db.php');
} else {
}

class tx_mkforms_widgets_mediaupload_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'widget_mediaupload_class' => 'res/js/mediaupload.js',
    ];

    public $bArrayValue = true;
    public $sMajixClass = 'MediaUpload';
    public $aPossibleCustomEvents = [
        'onajaxstart',
        'onajaxcomplete',
    ];

    public $aUploaded = false;    // array if file has just been uploaded

    private $uploadsWithoutReferences = [];

    private $uploadedMediaFiles = [];

    /**
     * folgendes brauch man um eine Liste der DAM/FAL Uploads auszugeben:.
                            <userobj>
                                <php><![CDATA[/*<?php
     */
    /**
     * diese zeile entfernen.
                                    return $currentFile['file_path'] . $currentFile['file_name'];
                                /*?>
     */

    /**
     * diese zeile entfernen.
     *
     * (non-PHPdoc)
     *
     * @see formidable_mainrenderlet::_render()
     */
    public function _render()
    {
        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
        $sessionIdForCurrentWidget = $this->getSessionIdForCurrentWidget();
        // hochgeladene Dateien in Session löschen wenn nicht submitted
        if (!$this->getForm()->getDataHandler()->_isSubmitted()) {
            $sessionData[$sessionIdForCurrentWidget.'_fileIds'] = '';
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $sessionData);
        }

        $this->includeLibraries();

        $sValue = $this->getValue();

        //wenn die datei hochgeladen wurde und der value wurde nicht richtig
        //gesetzt, dann holen wir das nach. das kann z.B. passieren wenn ein
        //anderes widget nicht validiert wurde weil dann der checkpoint in checkPoint()
        //nicht anspringt, der den value setzt. Das liegt am eigentlichen Bug beim setzen der
        //checkpoints. after-validation-ok wird NACH dem render der widgets gesetzt. Also
        //nachdem wir hier waren. Wir brauchen den value aber hier!
        if (!is_string($sValue) && $this->aUploaded) {
            $this->setValue($this->aUploaded['newSize']);
            $sValue = $this->aUploaded['newSize'];
        }

        $sLabel = $this->getLabel();
        $sInput = '<input type="file" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().' />';

        $aRes = [
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'input' => $sInput,
            'value' => $sValue,
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

    /**
     * @return string
     */
    private function getSessionIdForCurrentWidget()
    {
        return $GLOBALS['TSFE']->id.$this->_getElementHtmlId();
    }

    /**
     * @return array
     */
    public function getUploadedMediaFiles()
    {
        return $this->uploadedMediaFiles;
    }

    /**
     * @deprecated use getUploadedMediaFiles instead
     */
    public function getUploadedDamPics()
    {
        return $this->getUploadedMediaFiles();
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

    /**
     * @see api/formidable_mainrenderlet#checkPoint($aPoints)
     */
    public function checkPoint(&$aPoints, array &$options = [])
    {
        parent::checkPoint($aPoints, $options);

        // Die Verarbeitung der Datei unmittelbar nach der Initialisierung des DataHandlers starten
        if (in_array('after-init-datahandler', $aPoints)) {
            $this->manageFile();
        }

        // Bei Validierunfs-Fehlern muss die Referenz und die Datei wieder gelöscht werden!
        if (in_array('after-validation-nok', $aPoints)) {
            $this->manageFile(false);
            // wir müssen die renderlets noch refreshen damit der Lister neue Daten bekommt
            // es gibt keine anderen weg als das formular neu zu rendern
            if (isset($options['renderedRenderlets'])) {
                $options['renderedRenderlets'] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $this->getForm()->_renderElements(),
                    $this->getForm()->aPreRendered
                );
            }
        }

        // Die Value setzen, wenn Validierung OK war.
        if (in_array('after-validation-ok', $aPoints)) {
            $this->setValue($this->aUploaded['newSize']);
        }
    }

    /**
     * Hier startet bei einem normalen Submit die Verarbeitung der hochgeladenen Datei.
     */
    public function manageFile($valid = true)
    {
        $aData = $this->getValue();

        $uploadedFileIds = [];
        // die bisher hochgeladenen media IDs sammeln, damit wir diese auch in
        // einem lister ausgeben können
        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
        $sessionIdForCurrentWidget = $this->getSessionIdForCurrentWidget();
        if ($uploadedFileIdsFromSession = $sessionData[$sessionIdForCurrentWidget.'_fileIds']) {
            $uploadedFileIds = \Sys25\RnBase\Utility\Strings::trimExplode(',', $uploadedFileIdsFromSession);
        }

        if ($valid && (is_array($aData) && 0 == $aData['error'])) {
            // a file has just been uploaded
            $this->handleUpload($aData);

            // aktuellen upload hinzufügen
            if ($this->openUid) {
                $uploadedFileIds[] = $this->openUid;
            }
        } elseif (!$valid) {
            // Datei wurde hochgeladen und referenziert,
            // validation ist allerdings fehlgeschlagen.
            // Datei und Referenz löschen!
            if (!empty($this->aUploaded['mediaid'])) {
                $this->deleteReferences($this->aUploaded['mediaid']);
            }
            if (!empty($this->aUploaded['path'])) {
                $this->deleteFile(
                    $this->aUploaded['name'],
                    $this->aUploaded['mediaid']
                );
            }
            //auch für den Lister löschen
            foreach ($uploadedFileIds as $key => $mediaId) {
                if ($this->aUploaded['mediaid'] == $mediaId) {
                    unset($uploadedFileIds[$key]);
                }
            }
        } else {
            $this->handleNoUpload($aData);
        }

        // wurden bereits referenzen angelegt?
        $mediaFiles = $this->getReferencedMedia();

        // es wurden wahrscheinlich noch keine referenzen angelegt, sondern nur
        // dateien hochgeladen
        if ((empty($mediaFiles) || empty($mediaFiles['files'])) &&
            !empty($uploadedFileIds)
        ) {
            $mediaFiles = \Sys25\RnBase\Utility\TSFAL::getReferencesFileInfo($tableName, $this->getEntryId(), $fieldName);

            foreach ($mediaFiles as $uid => $mediaInfo) {
                // wird benötigt um in handleCreation die Referenzen anlegen zu können
                $this->uploadsWithoutReferences[$uid] = $uid;
            }
        }

        // jetzt kümmern wir uns um die Dateien, die gelöscht werden sollen
        $currentFileIds = []; // die DAM Ids, welche übrig sind nachdem gelöscht wurde
        // sollte eine checkbox sein
        $deleteWidgetName = $this->getForm()->_navConf('/deletewidget', $this->aElement);
        if (!empty($mediaFiles)) {
            foreach ($mediaFiles as $uid => $mediaInfo) {
                if ($deleteWidgetName &&
                    ($deleteWidget = $this->getForm()->getWidget($deleteWidgetName))
                ) {
                    $deleteWidget->setIteratingId($uid);
                    if ($deleteWidget->getValue()) {
                        unset($mediaFiles[$uid]);
                        unset($this->uploadsWithoutReferences[$uid]);
                        $this->deleteReferences($uid);
                        $this->deleteFile($mediaInfo['file_name'], $uid);
                        continue;
                    }
                }

                // kommen zurück in die session um diese nachdem submit anzeigen zu können
                $currentFileIds[] = $uid;
            }
        }

        $sessionData[$sessionIdForCurrentWidget.'_fileIds'] = join(',', $currentFileIds);
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $sessionData);
        $GLOBALS['TSFE']->fe_user->storeSessionData();

        // wird von tx_mkforms_util_DamUpload::getUploadsByWidget benötigt um die Liste
        // der DAM/FAL Uploads ausgeben zu können
        $this->uploadedMediaFiles = $mediaFiles;
    }

    /**
     * Returns data about new file on server.
     *
     * @param array $aData
     *
     * @return array keys: sTargetDir, sName, sTarget
     */
    public function getTargetFileData($aData)
    {
        $ret = [];
        if (false !== ($sTargetFile = $this->getTargetFile())) {
            $ret['sTargetDir'] = \Sys25\RnBase\Utility\T3General::dirname($sTargetFile);
            $ret['sName'] = basename($sTargetFile);
            $ret['sTarget'] = $sTargetFile;
        } else {
            $sTargetDir = $this->getTargetDir();

            $sName = $aData['name'];
            if ($this->getForm()->_defaultTrue('/data/cleanfilename', $this->aElement)) {
                $sName = tx_mkforms_util_Div::cleanupFileName($sName);
            }

            $sTarget = $sTargetDir.$sName;
            if (!$this->oForm->_defaultFalse('/data/overwrite', $this->aElement)) {
                // rename the file if same name already exists
                $sExt = ((false === strpos($sName, '.')) ? '' : '.'.substr(strrchr($sName, '.'), 1));
                for ($i = 1; file_exists($sTarget); ++$i) {
                    $sTarget = $sTargetDir.substr($sName, 0, strlen($sName) - strlen($sExt)).'_'.$i.$sExt;
                }
                $sName = basename($sTarget);
            }
            $ret['sTargetDir'] = $sTargetDir;
            $ret['sName'] = $sName;
            $ret['sTarget'] = $sTarget;
        }

        return $ret;
    }

    /**
     * ALGORITHM of file management.
                        1.1.1: multiple == TRUE
                            1.1.1.1: data *are not* stored in database (creation mode)
                                1.1.1.1.1: setValue to backupdata . newfilename
                            1.1.1.2: data *are* stored in database
                                1.1.1.2.1: setValue to storeddata . newfilename
     */
    private function handleUpload($aData)
    {
        $targetData = $this->getTargetFileData($aData);
        $sTarget = $targetData['sTarget'];
        $sName = $targetData['sName'];
        $sTargetDir = $targetData['sTargetDir'];
        $max = $this->getMaxObjects();
        $media = $this->getReferencedMedia();

        if ($max && count($media) >= $max) {
            $this->setValue(count($media));

            return;
        }
        if (!move_uploaded_file($aData['tmp_name'], $sTarget)) {
            tx_mkforms_util_Div::debug4ajax('FEHLER: '.$sTarget);

            return; // Error
        }

        // success
        $this->aUploaded = [
            'dir' => $sTargetDir,
            'name' => $sName,
            'path' => $sTarget,
            'infos' => $aData,
        ];

        // In Set Value kommt die Anzahl der Zuordnungen rein!
        // Bei nur einer erlaubten Zuordnung muss die ggf. vorhandene Datei dereferenziert werden
        if ($this->getForm()->isRunneable(($storageId = $this->getConfigValue('/data/storage/')))) {
            $storageId = (int) $this->getForm()->getRunnable()->callRunnableWidget($this, $storageId);
        }
        if (!is_numeric($storageId)) {
            throw new \InvalidArgumentException('No uid for Storage given (/data/storage/).');
        }

        $fileObject = \Sys25\RnBase\Utility\TSFAL::indexProcess($sTarget, $storageId);
        $mediaUid = $fileObject->getUid();

        // save mediauid
        $this->aUploaded['mediaid'] = $mediaUid;

        // Wir haben nun die UID des Bildes und müssen prüfen, ob es bereits zugeordnet ist
        $refFiles = $this->getReferencedMedia();

        if (is_array($refFiles) && array_key_exists($mediaUid, $refFiles)) {
            // The file is already referenced. Nothing to do
            $this->setValue($aData['backup']);

            return;
        }
        if (!$this->isMultiple()) {
            // Only one file is allowed. So remove all old references
            $this->deleteReferences();
        }
        // Bei der Neuanlage des Datensatzes gibt es noch keine UID für die Zuordnung. In dem Fall
        // müssen wir das später nachholen.
        $newSize = 1;
        if (!$this->getEntryId()) {
            // Wir sind bei der Neuanlage und haben noch keine UID. Daher merken wir uns die ID des Bildes
            $this->openUid = $mediaUid;
        } else {
            // Set the new reference
            $newSize = $this->addReference($mediaUid);
        }

        // save size
        $this->aUploaded['newSize'] = $newSize;

        return $newSize;
    }

    /**
     * Liefert die UID des Datensatzes, mit dem die Mediadatei verknüpft werden soll.
     * TODO: Ein Runnable setzen, damit der Wert bei Ajax-Calls gesetzt werden kann.
     *
     * @return uid
     */
    public function getEntryId()
    {
        $entryId = $this->getForm()->getConfigXML()->get('/data/refuid/', $this->aElement);
        if ($entryId) {
            $entryId = $this->getForm()->getRunnable()->callRunnable($entryId);

            return $entryId;
        }

        $entryId = (int) $this->getDataHandler()->entryId;
        // Im CreationMode steht die EntryID in einer anderen Variablen
        $entryId = $entryId ? $entryId : (int) $this->getDataHandler()->newEntryId;

        return $entryId;
    }

    /**
     * Call this method after insert of main record to set references.
     */
    public function handleCreation()
    {
        if (!$this->openUid && !$this->uploadsWithoutReferences) {
            return;
        }
        if ($this->openUid && !$this->uploadsWithoutReferences) {
            $mediaUids = [$this->openUid];
        } else {
            $mediaUids = $this->uploadsWithoutReferences;
        }

        foreach ($mediaUids as $mediaUid) {
            $this->addReference($mediaUid);
        }
        $this->openUid = 0;
    }

    /**
            2: file has not been uploaded
                2.1: data *are not* stored in database, as it's a creation mode not fully completed yet.
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
    public function handleNoUpload($aData)
    {
        // $aData ist normalerweise ein Array mit den Daten des Uploads. es kann aber auch ein String sein,
        // dann gab es aber wohl eine Sonderbehandlung
        // Für DAM ist ist aber wohl nicht relevant

        if (is_string($aData) && (true === $this->bForcedValue || trim($aData) !== $sStoredData)) {
            // Nothing to do here
            return;
        }

        // Hier holen wir den kompletten Record.
        $aStoredData = $this->getDataHandler()->_getStoredData();
        $cValue = $aStoredData[$this->_getName()];
        if ((false === $this->getDataHandler()->_edition()) || (!array_key_exists($this->_getName(), $aStoredData))) {
            // Keine Bearbeitung oder das Feld ist nicht im aktuellen Record
            $cValue = (is_array($aData) && array_key_exists('backup', $aData)) ? $aData['backup'] :
                                (is_string($aData) ? $aData : '');
        }
        $this->setValue($cValue);
    }

    /**
     * Returns the target dir.
     *
     * @return string
     */
    public function getTargetDir()
    {
        $oFileTool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getBasicFileUtilityClass());
        if ($this->oForm->isRunneable(($sTargetDir = $this->getConfigValue('/data/targetdir/')))) {
            $sTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $sTargetDir);
        }

        return \Sys25\RnBase\Utility\T3General::fixWindowsFilePath(
            $oFileTool->slashPath($sTargetDir)
        );
    }

    public function getTargetFile()
    {
        if (false !== ($mTargetFile = $this->getConfigValue('/data/targetfile'))) {
            if ($this->oForm->isRunneable($mTargetFile)) {
                $mTargetFile = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetFile);
            }

            if (is_string($mTargetFile) && '' !== trim($mTargetFile)) {
                return $this->oForm->toServerPath(trim($mTargetFile));
            }
        }

        return false;
    }

    /**
     * Returns the reference table for DAM/FAL.
     *
     * @return string
     */
    public function getRefTable()
    {
        if ($this->oForm->isRunneable(($uid = $this->getConfigValue('/data/reftable/')))) {
            $tableName = $this->getForm()->getRunnable()->callRunnableWidget($this, $uid);
        } else {
            $tableName = $this->getConfigValue('/data/reftable/', $this->aElement);
        }

        return (strlen($tableName)) ? $tableName : $this->getDataHandler()->tableName();
    }

    /**
     * Returns the a parameter maxobjects from the xml.
     *
     * @return string
     */
    public function getMaxObjects()
    {
        $maxobjects = $this->getConfigValue('/data/maxobjects/', $this->aElement);

        return (strlen($maxobjects)) ? $maxobjects : false;
    }

    /**
     * Returns the reference field for DAM/FAL.
     *
     * @return string
     */
    public function getRefField()
    {
        $fieldName = $this->getConfigValue('/data/reffield/', $this->aElement);

        return strlen($fieldName) ? $fieldName : $this->getAbsName();
    }

    /**
     * Returns the defined beuser id for DAM processing.
     *
     * @return int
     */
    public function getBeUserId()
    {
        if ($this->oForm->isRunneable(($uid = $this->getConfigValue('/data/beuser/')))) {
            $uid = $this->getForm()->getRunnable()->callRunnableWidget($this, $uid);
        }
        $uid = (int) $uid;

        return $uid ? $uid : 1;
    }

    /**
     * allow field to contain multiple files, comma-separated value.
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->oForm->_defaultFalse('/data/multiple', $this->aElement);
    }

    /**
     * Returns the form instance.
     *
     * @return tx_ameosformidable
     */
    public function getForm()
    {
        return parent::getForm();
    }

    /**
     * Returns the current data handler.
     *
     * @return formidable_maindatahandler
     */
    public function getDataHandler()
    {
        return $this->getForm()->oDataHandler;
    }

    /**
     * vorher die referenzen löschen da sonst die datei nicht gelöscht werden kann.
     *
     * @param string $sFile
     * @param int    $mediaUid
     */
    public function deleteFile($file, $mediaUid)
    {
        $refCount = \Sys25\RnBase\Utility\TSFAL::getReferencesCount($mediaUid);
        $tableName = 'sys_file';

        // wir löschen die Datei nur wenn keine Refrenzen mehr vorhanden sind
        if (0 === $refCount) {
            @unlink($this->getFullServerPath($file));
            \Sys25\RnBase\Database\Connection::getInstance()->doDelete($tableName, 'uid = '.$mediaUid);
        }
    }

    /**
     * Add a reference to a DAM media file
     * Problem: Im CreationMode haben wir für das Ziel-Objekt noch kein valide UID.
     * Diese ist erst vorhanden, wenn das Ziel-Objekt wirklich gespeichert wurde.
     *
     * @param int $mediaUid
     */
    protected function addReference($mediaUid)
    {
        $tableName = trim($this->getRefTable());
        $fieldName = $this->getRefField();
        $itemId = $this->getEntryId();

        \Sys25\RnBase\Utility\TSFAL::addReference($tableName, $fieldName, $itemId, $mediaUid);

        return \Sys25\RnBase\Utility\TSFAL::getImageCount($tableName, $fieldName, $itemId);
    }

    /**
     * Removes DAM/FAL references. If no parameter is given, all references will be removed.
     *
     * @param string $uids commaseperated uids
     */
    public function deleteReferences($uids = '')
    {
        $tableName = trim($this->getRefTable());
        $fieldName = $this->getRefField();
        $itemId = $this->getEntryId();
        \Sys25\RnBase\Utility\TSFAL::deleteReferences($tableName, $fieldName, $itemId);
    }

    /**
     * Returns all referenced media of current field.
     *
     * @return array keys: files and rows
     */
    public function getReferencedMedia()
    {
        if (!$this->getEntryId()) {
            // Ohne ID gibt es auch keine Bilder
            return [];
        }
        $tableName = trim($this->getRefTable());
        $fieldName = $this->getRefField();

        return \Sys25\RnBase\Utility\TSFAL::getReferencesFileInfo($tableName, $this->getEntryId(), $fieldName);
    }

    /**
     * DAM functionality requires a working BE. This method initializes all necessary stuff.
     */
    public function initBE4DAM($beUserId)
    {
        global $PAGES_TYPES, $BE_USER;
        if (!is_array($PAGES_TYPES) || !array_key_exists(254, $PAGES_TYPES)) {
            // SysFolder als definieren
            $PAGES_TYPES[254] = [
                'type' => 'sys',
                'icon' => 'sysf.gif',
                'allowedTables' => '*',
            ];
        }
        // Check BE User
        if (!is_object($BE_USER) || !is_array($BE_USER->user)) {
            if (!$beUserId) {
                $this->getForm()->mayday('NO BE User id given!');
            }
            unset($BE_USER);
            $BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getFrontendBackendUserAuthenticationClass());
            $BE_USER->setBeUserByUid($beUserId);
            $BE_USER->fetchGroupData();
            $BE_USER->backendSetUC();
            // Ohne Admin-Rechte gibt es leider Probleme bei der Verarbeitung mit der TCE.
            $BE_USER->user['admin'] = 1;
            $GLOBALS['BE_USER'] = $BE_USER;
        }

        if (!$GLOBALS['LANG']) {
            // Bei Ajax-Calls fehlt das Objekt
            $GLOBALS['LANG'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');
            $GLOBALS['LANG']->init($BE_USER->uc['lang']);
        }
    }

    private function includeLibraries()
    {
        if ($this->getForm()->issetAdditionalHeaderData('mkforms_damupload_includeonce')) {
            return;
        }

        if (!$this->getForm()->getConfigXML()->defaultFalse('/ajaxupload', $this->aElement)) {
            return;
        }

        $oJsLoader = $this->getForm()->getJSLoader();
        // JS-Lib ermitteln
        $dir = $oJsLoader->getJSFrameworkId();
        $sFile = \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL').$this->sExtRelPath.'res/js/'.$dir.'/ajaxfileupload.js';

        $oJsLoader->additionalHeaderData(
            '<script src="'.$oJsLoader->getScriptPath($sFile).'"></script>',
            'mkforms_mediaupload_includeonce'
        );
    }

    /**
     * Liefert den Namen der JS-Klasse des Widgets.
     *
     * @return string
     */
    protected function getMajixClass()
    {
        return ($this->getForm()->getConfigXML()->defaultFalse('/ajaxupload', $this->aElement))
            ? 'MediaUpload' : 'RdtBaseClass';
    }

    /**
     * Liefert die JS-Dateien, die für ein Widget eingebunden werden sollen.
     *
     * @return array
     */
    protected function getJSLibs()
    {
        if ($this->getForm()->getConfigXML()->defaultFalse('/ajaxupload', $this->aElement)) {
            return $this->aLibs;
        }

        return [];
    }

    /**
     * Im Ajax-Upload muss das Widget in einen postInit-Task initialisiert werden.
     *
     * @see api/formidable_mainrenderlet#includeScripts($aConfig)
     */
    public function includeScripts($aConf = [])
    {
        if ($this->getForm()->getConfigXML()->defaultFalse('/ajaxupload', $this->aElement)) {
            // Die Config um weitere Werte erweitern
            $url = $this->createUploadUrl();
            $aConf = [
                'uploadUrl' => $url,
            ];
        }
        parent::includeScripts($aConf);
        if (!$this->getForm()->getConfigXML()->defaultFalse('/ajaxupload', $this->aElement)) {
            return;
        }

        $button = $this->getForm()->getConfigXML()->get('/ajaxbutton', $this->aElement);
        if ($button) {
            $button = $this->getForm()->getWidget($button);
        }
        $button = $button ? $button->_getElementHtmlId() : '';

        $sAbsName = $this->getElementHtmlId();

        $sInitScript = <<<INITSCRIPT
        Formidable.f("{$this->getForm()->getFormId()}").o("{$sAbsName}").initAjaxUpload('{$button}');
INITSCRIPT;

        $this->getForm()->attachPostInitTask($sInitScript, 'postinit Ajax upload initialization', $this->_getElementHtmlId());
    }

    private function createUploadUrl()
    {
        $sThrower = $this->_getElementHtmlId();
        $sObject = 'widget_mediaupload';
        $sServiceKey = 'upload'; // Die Daten sind in der ext_localconf vordefiniert.
        $sFormId = $this->getForm()->getFormId();
        $sSafeLock = $this->_getSessionDataHashKey();
        $sUploadUrl = tx_mkforms_util_Div::removeEndingSlash(
            \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL')
        ).
            '/?mkformsAjaxId='.tx_mkforms_util_Div::getAjaxEId().'&object='.$sObject.'&servicekey='.$sServiceKey.'&formid='.$sFormId.'&safelock='.$sSafeLock.'&thrower='.$sThrower;

        tx_mkforms_session_Factory::getSessionManager()->initialize();
        $GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$sObject][$sServiceKey][$sSafeLock] = [
            'requester' => [
                'name' => $this->_getName(),
                'xpath' => $this->sXPath,
            ],
        ];

        return $sUploadUrl;
    }

    public function handleAjaxRequest($oRequest)
    {
        $aData = $this->getForm()->getRawFile(false, true);

        //Wir müssen uns das Element anhand der XML-Struktur aus $aData besorgen
        $path = [];
        $widget = $this;
        do {
            $path[] = $widget->getName();
            $widget = $widget->getParent();
        } while (is_object($widget));
        $myData = $aData;
        foreach (array_reverse($path) as $p) {
            $myData = $myData[$p];
        }

        //Validieren
        if ($validate = $this->getConfigValue('/validate')) {
            $errors = $this->getForm()->getValidationTool()->validateWidgets4Ajax(
                [$this->getName() => $myData]
            );
            if (count($errors)) {
                $this->getForm()->attachErrorsByJS($errors, $validate);
                // Replace value which contains the submitted data as ARRAY, while value must be a string!
                $this->setValue('');

                return [];
            } else {
                // wenn keine validationsfehler aufgetreten sind,
                // eventuell vorherige validierungs fehler entfernen
                $this->getForm()->attachErrorsByJS(null, $validate, true);
            }
        }

        if (is_array($myData) && 0 == $myData['error']) {
            // a file has just been uploaded
            $newSize = $this->handleUpload($myData);
        }

        return [
            [
                'data' => $newSize,
                'databag' => '{}',
                'method' => 'doNothing',
                'object' => $this->getAbsName(),
            ],
        ];
    }

    /**
     * (non-PHPdoc).
     *
     * @see formidable_mainrenderlet::isSaveable()
     */
    public function isSaveable()
    {
        // widget value should not be processed by datahandler
        return false;
    }
}

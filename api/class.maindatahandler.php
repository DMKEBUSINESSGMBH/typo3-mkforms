<?php

class formidable_maindatahandler extends formidable_mainobject
{
    public $entryId;

    public $forcedId;        // wether $entryId id forced by the PHP, or not

    public $newEntryId;

    public $bDataHandlerOnSubmit = false;    // fills an empty field with data from the datahendler

    public $__aStoredData = [];    // internal use only

    public $__aFormData = [];    // internal use only

    public $__aFormDataManaged = [];    // internal use only

    public $__aCols = [];                // columns associated to an existing renderlet

    public $__aListData = [];            // contextual data, containing the current list record

    public $__aParentListData = [];            // contextual data, containing the current list record

    public $aT3Languages = false;

    public $bHasCreated = false;

    public $bHasEdited = false;

    public $aProcessBeforeRenderData = false;

    /**
     * @param tx_ameosformidable $oForm
     * @param array              $aElement
     * @param array              $aObjectType
     * @param string             $sXPath
     */
    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        parent::_init($oForm, $aElement, $aObjectType, $sXPath);

        if (!is_null($dhos = $oForm->getConfTS('datahandleronsubmit'))) {
            $this->bDataHandlerOnSubmit = $this->isTrueVal($dhos);
        }
        if (false !== ($dhos = $this->_navConf('/datahandleronsubmit'))) {
            $this->bDataHandlerOnSubmit = $this->isTrueVal($dhos);
        }

        if ($this->i18n()) {
            if (false === $this->i18n_getDefLangUid()) {
                tx_mkforms_util_Div::mayday('DATAHANDLER: <b>/i18n/use</b> is active but no <b>/i18n/defLangUid</b> given');
            }
        }
    }

    /**
     * Processes data returned by the HTML Form after validation, and only if validated
     * Note that this is only the 'abstract' definition of this function
     *  as it must be overloaded in the specialized DataHandlers.
     */
    public function _doTheMagic($bShouldProcess = true)
    {
    }

    /**
     * Returns the slashstripped GET vars array.
     *
     * @return array GET vars array
     *
     * @see    formidable_maindatahandler::_GP()
     */
    public function _G()
    {
        return $this->getForm()->_getRawGet();
    }

    /**
     * Returns the slashstripped POST vars array
     *  merged with the _FILES vars array.
     *
     * @return array POST vars array
     *
     * @see    formidable_maindatahandler::_GP()
     */
    public function _P($sName = false)
    {
        $aRawPost = $this->getForm()->_getRawPost();
        if (false !== $sName) {
            if (array_key_exists($sName, $aRawPost)) {
                return $aRawPost[$sName];
            } else {
                return '';
            }
        }

        return $aRawPost;
    }

    /**
     * Returns the slashstripped _FILES vars array.
     *
     * @return array _FILES vars array
     *
     * @see    formidable_maindatahandler::_P()
     */
    public function _F()
    {
        return $this->getForm()->_getRawFile();
    }

    public function groupFileInfoByVariable(&$top, $info, $attr)
    {
        return $this->getForm()->groupFileInfoByVariable($top, $info, $attr);
    }

    /**
     * Returns the merged GET and POST arrays
     *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
     *  and therefore not slashstripped.
     *
     *    POST overrides GET
     *
     * @return array GET and POST vars array
     */
    public function _GP()
    {
        return \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
            $this->_G(),
            $this->_P()
        );
    }

    /**
     * Returns the merged GET and POST arrays
     *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
     *  and therefore not slashstripped.
     *
     *    GET overrides POST
     *
     * @return array GET and POST vars array
     */
    public function _PG()
    {
        return \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
            $this->_P(),
            $this->_G()
        );
    }

    /**
     * Determines if the FORM is submitted
     *  using the AMEOSFORMIDABLE_SUBMITTED constant for naming the POSTED variable.
     *
     * @return bool
     */
    public function _getSubmittedValue($sFormId = false)
    {
        return $this->getForm()->getSubmittedValue($sFormId);
    }

    public function _isSubmitted($sFormId = false)
    {
        return $this->_isFullySubmitted($sFormId) || $this->_isRefreshSubmitted($sFormId) || $this->_isTestSubmitted($sFormId)
            || $this->_isDraftSubmitted($sFormId)
            || $this->_isClearSubmitted($sFormId)
            || $this->_isSearchSubmitted($sFormId);
    }

    public function _isFullySubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_FULL == $this->_getSubmittedValue($sFormId);
    }

    public function _isRefreshSubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH == $this->_getSubmittedValue($sFormId);
    }

    public function _isTestSubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_TEST == $this->_getSubmittedValue($sFormId);
    }

    public function _isDraftSubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT == $this->_getSubmittedValue($sFormId);
    }

    public function _isClearSubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR == $this->_getSubmittedValue($sFormId);
    }

    public function _isSearchSubmitted($sFormId = false)
    {
        return AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH == $this->_getSubmittedValue($sFormId);
    }

    public function getSubmitter($sFormId = false)
    {
        return $this->getForm()->getSubmitter($sFormId);
    }

    public function getFormData()
    {
        reset($this->__aFormData);

        return $this->__aFormData;
    }

    public function _getFormData()
    {
        return $this->getFormData();
    }

    public function getThisFormData($sName)
    {
        $oRdt = $this->getForm()->rdt($sName);
        $sAbsName = $oRdt->getAbsName();
        $sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);

        return $this->getForm()->navDeepData($sAbsPath, $this->__aFormData);
    }

    public function _getThisFormData($sAbsName)
    {
        return $this->getThisFormData($sAbsName);
    }

    public function _processBeforeRender($aData)
    {
        if (false !== ($mRunneable = $this->_navConf('/process/beforerender/'))) {
            if ($this->getForm()->isRunneable($mRunneable)) {
                $aData = $this->callRunneable(
                    $mRunneable,
                    $aData
                );

                if (!is_array($aData)) {
                    $aData = [];
                }

                reset($aData);

                return $aData;
            }
        }

        return false;
    }

    public function getFormDataManaged()
    {
        $this->getForm()->mayday('getFormDataManaged() is deprecated');

        return $this->_getFormDataManaged();
    }

    public function _getFormDataManaged()
    {
        $this->getForm()->mayday('_getFormDataManaged() is deprecated');
        if (empty($this->__aFormDataManaged)) {
            $this->__aFormDataManaged = [];
            $aKeys = array_keys($this->getForm()->aORenderlets);

            reset($aKeys);
            foreach ($aKeys as $sAbsName) {
                if (!$this->getForm()->getWidget($sAbsName)->_renderOnly() && !$this->getForm()->getWidget($sAbsName)->_readOnly()
                    && $this->getForm()->getWidget($sAbsName)->hasBeenDeeplySubmitted()
                ) {
                    $this->__aFormDataManaged[$sAbsName] = $this->getForm()->getWidget($sAbsName)->getValue();
                }
            }
        }

        reset($this->__aFormDataManaged);

        return $this->__aFormDataManaged;
    }

    public function _getFlatFormData()
    {
        $this->getForm()->mayday('_getFlatFormData() is deprecated');
        $aFormData = $this->_getFormData();
        $aRes = [];
        reset($aFormData);
        foreach ($aFormData as $sName => $mData) {
            if (array_key_exists($sName, $this->getForm()->aORenderlets)) {
                $aRes[$sName] = $this->getForm()->aORenderlets[$sName]->_flatten($mData);
            }
        }

        reset($aRes);

        return $aRes;
    }

    public function _getFlatFormDataManaged()
    {
        $this->getForm()->mayday('_getFlatFormDataManaged() is deprecated');
        $aFormData = $this->_getFormDataManaged();

        $aFlatFormDataManaged = [];
        reset($aFormData);
        foreach ($aFormData as $sAbsName => $mData) {
            if (array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
                if ($this->getForm()->useNewDataStructure()) {
                    $this->getForm()->mayday('not implemented yet:'.__FILE__.':'.__LINE__);
                    // data will be stored under abs name
                } else {
                    if (!$this->getForm()->getWidget($sAbsName)->_renderOnly()
                        && !$this->getForm()
                            ->getWidget($sAbsName)
                            ->_readOnly()
                    ) {
                        // FormDataManaged strips readonly fields
                        // whereas since revision 200, FormData don't

                        $sNewName = $this->getForm()->getWidget($sAbsName)->getName();
                        $aFlatFormDataManaged[$sNewName] = $this->getForm()->getWidget($sAbsName)->_flatten($mData);
                    }
                }
            }
        }

        reset($aFlatFormDataManaged);

        return $aFlatFormDataManaged;
    }

    /**
     * Determines if something was not validated during the validation process.
     *
     * @deprecated use getForm()->getValidationTool()->isAllValid() from form!
     *
     * @return bool TRUE if everything is valid, FALSE if not
     */
    public function _allIsValid()
    {
        return $this->getForm()->getValidationTool()->isAllValid();
    }

    public function _isValid($sAbsName)
    {
        if (is_array($this->getForm()->_aValidationErrors) && array_key_exists($sAbsName, $this->getForm()->_aValidationErrors)) {
            $sElementHtmlId = $this->getForm()->getWidget($sAbsName)->_getElementHtmlId();
            if (array_key_exists($sElementHtmlId, $this->getForm()->_aValidationErrorsByHtmlId)) {
                return false;
            }
        }

        return true;
    }

    public function edition()
    {
        return $this->_edition();
    }

    public function creation()
    {
        return $this->_creation();
    }

    /**
     * Determines if the DataHandler should work in 'edition' mode
     * Note that this is only the 'abstract' definition of this function
     *  in the simple case where your DataHandler should never have to edit data.
     *
     * @return bool TRUE if edition mode, FALSE if not
     */
    public function _edition()
    {
        return false;
    }

    public function _creation()
    {
        return !$this->_edition();
    }

    /**
     * Gets the data previously stored by the DataHandler
     * for edition
     * Note that this is only the 'abstract' definition of this function
     *  in the simple case where your DataHandler should never have to edit data.
     *
     * @param string|bool $sName
     *
     * @return array|string
     *
     * @see    formidable_maindatahandler::_edition()
     */
    public function _getStoredData($sName = false)
    {
        if (false !== $sName) {
            return '';
        }

        return [];
    }

    /**
     * @param string|bool $sName
     *
     * @return array|string
     */
    public function getStoredData($sName = false)
    {
        return $this->_getStoredData($sName);
    }

    public function refreshStoredData()
    {
        $this->__aStoredData = []; // Ist notwendig, da direkt auf das Array zugegriffen wird!
        $this->__aStoredData = $this->getStoredData();
        // Jetzt initRecord abfahren
        if (false !== ($val = $this->getForm()->getConfig()->get('/control/datahandler/initrecord'))) {
            $this->__aStoredData = $this->getForm()->getRunnable()->callRunnable($val, $this->__aStoredData);
        }
    }

    public function refreshFormData()
    {
        $this->__aFormData = [];
        $aKeys = array_keys($this->getForm()->aORenderlets);
        reset($aKeys);
        foreach ($aKeys as $sAbsName) {
            if (!$this->getForm()->getWidget($sAbsName)->hasParent()) {
                $sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
                $this->getForm()->setDeepData(
                    $sAbsPath,
                    $this->__aFormData,
                    $this->getRdtValue($sAbsName)
                );
            }
        }

        $this->getForm()->checkPoint(
            [
                'after-fetching-formdata',
            ]
        );

        $this->aProcessBeforeRenderData = false;

        if (false !== ($aNewData = $this->_processBeforeRender($this->__aFormData))) {
            $aDiff = $this->getForm()->array_diff_recursive($aNewData, $this->__aFormData);
            if (count($aDiff) > 0) {
                $this->aProcessBeforeRenderData = $aDiff;
            }
        }
    }

    public function alterVirginData($aData)
    {
        if (false !== ($mRun = $this->_navConf('/altervirgindata'))) {
            if ($this->getForm()->isRunneable($mRun)) {
                return $this->callRunneable($mRun, $aData);
            }
        }

        return $aData;
    }

    public function alterSubmittedData($aData)
    {
        if (false !== ($mRun = $this->_navConf('/altersubmitteddata'))) {
            if ($this->getForm()->isRunneable($mRun)) {
                return $this->callRunneable($mRun, $aData);
            }
        }

        return $aData;
    }

    public function refreshAllData()
    {
        $this->refreshStoredData();
        $this->refreshFormData();
    }

    public function currentId()
    {
        return $this->_currentEntryId();
    }

    public function currentEntryId()
    {
        return $this->_currentEntryId();
    }

    public function _currentEntryId()
    {
        if (!is_null($this->newEntryId)) {
            return $this->newEntryId;
        }

        if (!is_null($this->entryId)) {
            return $this->entryId;
        }

        if ($this->_isSubmitted() && !$this->_isClearSubmitted()) {
            $form_id = $this->getForm()->formid;

            $aPost = \Sys25\RnBase\Utility\T3General::_POST();

            $aPost = is_array($aPost[$form_id] ?? null) ? $aPost[$form_id] : [];
            $aFiles = is_array($GLOBALS['_FILES'][$form_id] ?? null) ? $GLOBALS['_FILES'][$form_id] : [];
            $aP = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule($aPost, $aFiles);

            \Sys25\RnBase\Utility\T3General::stripSlashesOnArray($aP);

            if (array_key_exists('AMEOSFORMIDABLE_ENTRYID', $aP) && '' !== trim($aP['AMEOSFORMIDABLE_ENTRYID'])) {
                return (int) $aP['AMEOSFORMIDABLE_ENTRYID'];
            }
        }

        return false;
    }

    public function getHumanFormData()
    {
        return $this->_getHumanFormData();
    }

    public function _getHumanFormData()
    {
        $aFormData = $this->_getFormData();

        $aValues = [];
        $aLabels = [];

        reset($aFormData);
        foreach ($aFormData as $elementname => $value) {
            if (array_key_exists($elementname, $this->getForm()->aORenderlets)) {
                $aValues[$elementname] = $this->getForm()->aORenderlets[$elementname]->_getHumanReadableValue($value);
                $aLabels[$elementname] = $this->getForm()->getConfigXML()->getLLLabel(
                    $this->getForm()->aORenderlets[$elementname]->aElement['label']
                );
            }
        }

        reset($aValues);
        reset($aLabels);

        return [
            'labels' => $aLabels,
            'values' => $aValues,
        ];
    }

    public function _initCols()
    {
        $this->__aCols = [];
    }

    public function getListData($sKey = false)
    {
        return $this->_getListData($sKey);
    }

    public function isIterating()
    {
        return false !== $this->__aListData;
    }

    public function _getListData($sKey = false)
    {
        if (false === $this->__aListData) {
            return false;
        }

        $iLastListData = (count($this->__aListData) - 1);
        if ($iLastListData < 0) {
            return false;
        }

        if (false !== $sKey) {
            if (array_key_exists($sKey, $this->__aListData[$iLastListData])) {
                return $this->__aListData[$iLastListData][$sKey];
            } else {
                return false;
            }
        } else {
            if (!empty($this->__aListData)) {
                return $this->__aListData[$iLastListData];
            }
        }

        return [];
    }

    public function _getParentListData($sKey = false)
    {
        if (false !== $sKey) {
            if (array_key_exists($sKey, $this->__aParentListData)) {
                reset($this->__aParentListData);

                return $this->__aParentListData[$sKey];
            } else {
                return false;
            }
        } else {
            reset($this->__aParentListData);

            return $this->__aParentListData;
        }
    }

    public function i18n()
    {
        return $this->_defaultFalse('/i18n/use');
    }

    public function i18n_getSysLanguageUid()
    {
        // http://lists.netfielders.de/pipermail/typo3-at/2005-November/007373.html

        if (false !== $this->getForm()->rdt('sys_language_uid')) {
            return $this->getForm()->rdt('sys_language_uid')->getValue();
        } else {
            return $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
        }
    }

    public function i18n_getChildRecords($iParentUid)
    {
        if (false !== ($sTableName = $this->tableName())) {
            $aRecords = [];

            $rows = \Sys25\RnBase\Database\Connection::getInstance()->doSelect(
                '*',
                $sTableName,
                ['where' => "l18n_parent='".$iParentUid."'"]
            );

            foreach ($rows as $aRs) {
                $aRecords[$aRs['sys_language_uid']] = $aRs;
            }

            if (!empty($aRecords)) {
                reset($aRecords);

                return $aRecords;
            }
        }

        return [];
    }

    public function i18n_getDefLangUid()
    {
        return $this->_navConf('/i18n/deflanguid');
    }

    public function getT3Languages()
    {
        if (false === $this->aT3Languages) {
            $this->aT3Languages = [];

            $databaseConnection = \Sys25\RnBase\Database\Connection::getInstance();
            $rows = $databaseConnection->doSelect(
                '*',
                'sys_language',
                ['where' => '1=1'.$databaseConnection->enableFields('sys_language')]
            );

            foreach ($rows as $aRs) {
                $this->aT3Languages[$aRs['uid']] = $aRs;
            }
        }

        reset($this->aT3Languages);

        return $this->aT3Languages;
    }

    public function i18n_currentRecordUsesDefaultLang()
    {
        return false;
    }

    public function tableName()
    {
        return $this->getForm()->_navConf('/control/datahandler/tablename');
    }

    public function keyName()
    {
        if (false === ($sKey = $this->getForm()->_navConf('/control/datahandler/keyname'))) {
            return 'uid';
        }

        return $sKey;
    }

    public function newI18nRequested()
    {
        return false;
    }

    public function i18n_getValueDefaultLang()
    {
        if ($this->i18n()) {
        }
    }

    public function i18n_getStoredParent($bStrict = true)
    {
        return false;
    }

    public function i18n_getThisStoredParent($sField, $bStrict = true)
    {
        if (false !== ($aStoredParent = $this->i18n_getStoredParent($bStrict))) {
            if (array_key_exists($sField, $aStoredParent)) {
                return $aStoredParent[$sField];
            }
        }

        return false;
    }

    public function getRdtValue($sAbsName)
    {
        if (!array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
            return '';
        }

        if (true === $this->getForm()->getWidget($sAbsName)->bForcedValue) {
            return $this->getForm()->getWidget($sAbsName)->mForcedValue;
        }

        if ($this->getForm()->getWidget($sAbsName)->i18n_shouldNotTranslate()) {
            if (false !== ($aStoredI18NParent = $this->i18n_getStoredParent(true))) {
                // TODO: do a better mapping between rdt name and the data structure
                // like databridges, see $this->getRdtValue_noSubmit_edit()

                $sLocalName = $this->getForm()->getWidget($sAbsName)->getName();
                if (array_key_exists($sLocalName, $aStoredI18NParent)) {
                    return $this->getForm()->getWidget($sAbsName)->_unFlatten(
                        $aStoredI18NParent[$sLocalName]
                    );
                }
            }
        } elseif ($this->getForm()->getWidget($sAbsName)->_isClearSubmitted()) {
            if ($this->getForm()->getWidget($sAbsName)->_edition()) {
                return $this->getRdtValue_noSubmit_edit($sAbsName);
            } else {
                return $this->getRdtValue_noSubmit_noEdit($sAbsName);
            }
        } elseif ($this->getForm()->getWidget($sAbsName)->_isSubmitted()) {
            if (false !== $this->getForm()->iForcedEntryId) {
                // we have to use a fresh new record from database
                // so let noSubmit_edit do the job (meaning: don't consider values from submitted POST, but only those from DB)

                return $this->getRdtValue_noSubmit_edit($sAbsName);
            } else {
                $widget = $this->getForm()->getWidget($sAbsName);
                $mValue = $widget->_getValue();
                if (false === $mValue) {
                    if ($widget->_readOnly()) {
                        if ($widget->_edition()) {
                            return $this->getRdtValue_submit_readonly_edition($sAbsName);
                        } else {
                            return $this->getRdtValue_submit_readonly_noEdition($sAbsName);
                        }
                    } else {
                        if ($widget->_edition()) {
                            return $this->getRdtValue_submit_edition($sAbsName);
                        } else {
                            return $this->getRdtValue_submit_noEdition($sAbsName);
                        }
                    }
                }

                return $mValue;
            }
        } else {
            if ($this->getForm()->getWidget($sAbsName)->_edition()) {
                return $this->getRdtValue_noSubmit_edit($sAbsName);
            } else {
                return $this->getRdtValue_noSubmit_noEdit($sAbsName);
            }
        }
    }

    /**
     * prüft ob alle daten in dem array auch durch tatsächliche widgets
     * repräsentiert werden.
     *
     * @param array  $aGP      das array mit den widgets und deren wert. für geöhnlich $_GET und $_POST
     * @param string $sAbsName der absolute name des aktuellen widgets
     *
     * @todo auch bei listern prüfen ob übergebene daten auch als column im xml sind. tests bereits erstellt und schlagen aktuell
     *       fehl!
     */
    protected function checkWidgetsExist(&$aGP, $sAbsName)
    {
        // wenn es kein widget ist, dann setzen wir den wert auf null
        // um manipulationen zu verhindern. Sonst könnten willkürlich
        // werte mitgeschickt werden
        if (!isset($this->getForm()->aORenderlets[$sAbsName])) {
            $aGP = null;

            return;
        } else {
            $this->getForm()->aORenderlets[$sAbsName]->checkValue($aGP);
        }
    }

    public function getRdtValue_submit_edition($sAbsName)
    {
        $widget = $this->getForm()->getWidget($sAbsName);
        $sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
        $sRelPath = is_object($widget) ? $widget->getName() : '';

        // Hier gibt es anscheinend einen Einstiegspunkt, um den aktuellen Wert zu manipulieren
        if (false !== $this->aProcessBeforeRenderData
            && (false !== ($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData))
                || false !== ($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)))
        ) {
            return $widget->_unFlatten($mValue);
        }

        $aGP = $this->_GP();
        // Das ist für die einfachen Widgets ohne Boxen
        if (array_key_exists($sAbsName, $aGP)) {
            // es werden nur Renderlets akzeptiert, die auch im XML vorhanden sind!!!
            if ($this->getForm()->getConfTS('checkWidgetsExist')) {
                $this->checkWidgetsExist($aGP[$sAbsName], $sAbsName);
            }

            return $aGP[$sAbsName];
        }

        if (array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
            // converting abs name to htmlid to introduce lister rowuids in the path
            $sHtmlId = $widget->getElementId();

            // removing the formid. prefix
            $sHtmlId = substr($sHtmlId, strlen($this->getForm()->getFormId().AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN));

            // converting id to data path
            $sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sHtmlId);
            if (false !== ($aRes = $this->getForm()->navDeepData($sAbsPath, $aGP))) {
                // es werden nur Renderlets akzeptiert, die auch im XML vorhanden sind!!!
                if ($this->getForm()->getConfTS('checkWidgetsExist')) {
                    $this->checkWidgetsExist($aRes, $sAbsName);
                }

                return $aRes;
            } elseif ($this->bDataHandlerOnSubmit) {
                // Es wurde keine Daten für dieses Feld submitted (modalbax!)
                // wir holen uns also die daten vom datahandler, wenn gewünscht
                // ACHTUNG es werden bei einem leerem feld(checkbox) immer daten geliefert
                // obwohl diese gelöscht werden sollen.

                $sNewName = $this->getForm()->getWidget($sAbsName)->getName();

                $aStored = $this->_getStoredData();

                if (is_array($aStored) && array_key_exists($sNewName, $aStored)) {
                    return $this->getForm()->getWidget($sAbsName)->_unFlatten($aStored[$sNewName]);
                }
            }
        }

        // get defaultValue if no value is set
        if (is_object($widget) && false !== ($mValue = $widget->_getDefaultValue())) {
            return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
        }

        return '';
    }

    public function getRdtValue_submit_noEdition($sName)
    {
        return $this->getRdtValue_submit_edition($sName);
    }

    public function getRdtValue_submit_readonly_edition($sName)
    {
        // there is a bug here, as renderlet:BOX is readonly
        // and so nothing in a box might be submitted ?!
        if ($this->getForm()->aORenderlets[$sName]->hasChilds()) {
            // EDIT: bug might be solved with this hasChilds() test
            return $this->getRdtValue_submit_noEdition($sName);
        } else {
            return $this->getRdtValue_noSubmit_edit($sName);
        }
    }

    public function getRdtValue_submit_readonly_noEdition($sName)
    {
        $aGP = $this->_GP();

        $sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sName);

        if (false !== ($mValue = $this->getForm()->aORenderlets[$sName]->_getValue())
        ) {            // value is prioritary if submitted
            return $this->getForm()->aORenderlets[$sName]->_unFlatten($mValue);
        } elseif (false !== ($mValue = $this->getForm()->aORenderlets[$sName]->_getDefaultValue())) {
            return $this->getForm()->aORenderlets[$sName]->_unFlatten($mValue);
        } elseif (false !== ($mValue = $this->getForm()->navDeepData($sPath, $aGP))) {
            // if rdt has no childs, do not use the posted data, as it will contain the post-flag "1"
            if ($this->getForm()->aORenderlets[$sName]->hasChilds()) {
                // this is needed as refreshFormData() only works on root-renderlets (no parents)
                // thus the renderlets have to fetch the data of their descendants themselves
                // this is, for instance, the case for renderlet:BOX
                return $this->getForm()->aORenderlets[$sName]->_unFlatten($mValue);
            }
        }

        return '';
    }

    /**
     * Liefert den Wert eines Widgets.
     *
     * @param $sName
     *
     * @return string
     */
    public function getRdtValue_noSubmit_noEdit($sName)
    {
        if (!array_key_exists($sName, $this->getForm()->aORenderlets)) {
            return '';
        }

        $aGP = $this->_isClearSubmitted() ? $this->_G() : $this->_GP();

        $widget = $this->getForm()->getWidget($sName);
        $sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sName);
        $sRelPath = $widget->getName();

        if (false !== ($mValue = $widget->_getValue())) {
            return $widget->_unFlatten($mValue);
        } elseif (false !== $this->aProcessBeforeRenderData
            && (false !== ($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData))
                || false !== ($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)))
        ) {
            return $widget->_unFlatten($mValue);
        } elseif (false !== ($mValue = $this->getForm()->navDeepData($sPath, $aGP))) {
            return $widget->_unFlatten($mValue);
        } elseif (false !== ($mValue = $widget->_getDefaultValue())) {
            return $widget->_unFlatten($mValue);
        }
    }

    public function getRdtValue_noSubmit_edit($sAbsName)
    {
        if (array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
            if (false !== ($mValue = $this->getForm()->getWidget($sAbsName)->_getValue())) {    // value a toujours le dessus
                return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
            } else {
                $mRes = null;

                if ($this->getForm()->getWidget($sAbsName)->hasDataBridge()) {
                    $oDataSet = $this->getForm()->getWidget($sAbsName)->dbridged_getCurrentDsetObject();

                    // sure that dataset is anchored, as we already tested it to be in noSubmit_edit
                    $aData = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(// allowing GET to set values
                        $oDataSet->getData(),
                        $this->_G()
                    );

                    if (false !== ($sMappedPath = $this->getForm()->getWidget($sAbsName)->dbridged_mapPath())) {
                        if (false !== ($mData = $this->getForm()->navDeepData($sMappedPath, $aData))) {
                            $mRes = $mData;
                        }
                    }
                } else {
                    $sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
                    $sRelPath = $this->getForm()->getWidget($sAbsName)->getName();

                    if (false !== $this->aProcessBeforeRenderData
                        && (false !== ($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData))
                            || false !== ($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)))
                    ) {
                        return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
                    }

                    $sNewName = $this->getForm()->getWidget($sAbsName)->getName();

                    $aStored = $this->_getStoredData();

                    if (is_array($aStored)) {
                        $aData = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(// allowing GET to set values
                            $aStored,
                            $this->_G()
                        );

                        if (array_key_exists($sNewName, $aData)) {
                            $mRes = $this->getForm()->getWidget($sAbsName)->_unFlatten($aData[$sNewName]);
                        }
                    }
                }
                // @TODO War auskommentiert, warum!?
                if (is_null($mRes) || $this->getForm()->getWidget($sAbsName)->_emptyFormValue($mRes)) {
                    if (false !== ($mValue = $this->getForm()->getWidget($sAbsName)->_getDefaultValue())) {
                        return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
                    }
                }

                return $mRes;
            }
        }

        return '';
    }
}

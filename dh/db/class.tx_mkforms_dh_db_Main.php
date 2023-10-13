<?php
/**
 * Plugin 'dh_db' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_dh_db_Main extends formidable_maindatahandler
{
    public $__aStoredI18NParent = false;

    public function _doTheMagic($bShouldProcess = true)
    {
        $tablename = $this->tableName();
        $keyname = $this->keyName();

        if ($this->getForm()->getDataHandler()->_isDraftSubmitted()
            && !$this->getForm()->_defaultTrue(
                '/control/datahandler/processondraft'
            )
        ) {
            $bShouldProcess = false;
        }

        if ('' != $tablename && '' != $keyname) {
            if ($this->i18n() && false !== ($aNewI18n = $this->newI18nRequested())) {
                // first check that parent exists
                if (false === ($aParent = $this->__getDbData($tablename, $keyname, $aNewI18n['i18n_parent']))) {
                    $this->oForm->mayday(
                        'DATAHANDLER DB cannot create requested i18n for non existing parent:'.$aNewI18n['i18n_parent']
                    );
                }

                //then check that no i18n record exists for requested sys_language_uid on this parent record
                $rows = \Sys25\RnBase\Database\Connection::getInstance()->doSelect(
                    $keyname,
                    $tablename,
                    ['where' => "l18n_parent='".$aNewI18n['i18n_parent']."' AND sys_language_uid='".$aNewI18n['sys_language_uid']."'"]
                );

                if ($rows) {
                    $this->oForm->mayday(
                        'DATAHANDLER DB cannot create requested i18n for parent:'.$aNewI18n['i18n_parent']
                        .' with sys_language_uid:'.$aNewI18n['sys_language_uid'].' ; this version already exists'
                    );
                }

                $aChild = [];
                $aChild['sys_language_uid'] = $aNewI18n['sys_language_uid'];
                $aChild['l18n_parent'] = $aNewI18n['i18n_parent'];    // notice difference between i and l
                $aChild['crdate'] = $GLOBALS['EXEC_TIME'];
                $aChild['tstamp'] = $GLOBALS['EXEC_TIME'];
                $aChild['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
                $aChild['pid'] = $aParent['pid'];

                $this->newEntryId = \Sys25\RnBase\Database\Connection::getInstance()->doInsert($tablename, $aChild);
                $this->bHasCreated = true;
                $this->refreshAllData();
            }

            if ($bShouldProcess && $this->getForm()->getValidationTool()->isAllValid()) {
                // il n'y a aucune erreur de validation
                // on peut traiter les donnes
                // on met a jour / insere l'enregistrement dans la base de donnees

                $aFormData = $this->_processBeforeInsertion(
                    $this->getDataPreparedForDB()
                );

                if (count($aFormData) > 0) {
                    $aFormData = $this->cleanNonTcaFields($aFormData);
                    $editEntry = $this->_currentEntryId();

                    if ($editEntry) {
                        $aFormData = $this->_processBeforeEdition($aFormData);

                        if ($this->i18n() && $this->i18n_updateChildsOnSave() && $this->i18n_currentRecordUsesDefaultLang()) {
                            // updating non translatable child data

                            $aUpdateData = [];

                            $this->oForm->_debug(
                                '',
                                'DB update, taking care of sys_language_uid '.$this->i18n_getSysLanguageUid()
                            );

                            reset($aFormData);
                            foreach ($aFormData as $sName => $notNeeded) {
                                if (!array_key_exists($sName, $this->oForm->aORenderlets)
                                    || !$this->oForm->aORenderlets[$sName]->_translatable()
                                ) {
                                    $aUpdateData[$sName] = $aFormData[$sName];
                                }
                            }

                            if (!empty($aUpdateData)) {
                                $this->oForm->_debug(
                                    $aUpdateData,
                                    'EXECUTION OF DATAHANDLER DB - EDITION MODE in '.$tablename.'['.$keyname.'='
                                    .$editEntry.'] - UPDATING NON TRANSLATED I18N CHILDS'
                                );

                                \Sys25\RnBase\Database\Connection::getInstance()->doUpdate(
                                    $tablename,
                                    "l18n_parent = '".$editEntry."'",
                                    $aUpdateData
                                );
                            }
                        }

                        if ($this->fillStandardTYPO3fields()) {
                            if (!array_key_exists('tstamp', $aFormData)) {
                                $aFormData['tstamp'] = $GLOBALS['EXEC_TIME'];
                            }
                        }

                        $this->oForm->_debug(
                            $aFormData,
                            'EXECUTION OF DATAHANDLER DB - EDITION MODE in '.$tablename.'['.$keyname.'='.$editEntry
                            .']'
                        );

                        \Sys25\RnBase\Database\Connection::getInstance()->doUpdate(
                            $tablename,
                            $keyname." = '".$editEntry."'",
                            $aFormData
                        );

                        $this->oForm->_debug(
                            \Sys25\RnBase\Database\Connection::getInstance()->getDatabaseConnection()->debug_lastBuiltQuery,
                            'DATAHANDLER DB - SQL EXECUTED'
                        );

                        // updating stored data
                        if (!is_array($this->__aStoredData)) {
                            $storedData = [];
                        } else {
                            $storedData = $this->__aStoredData;
                        }
                        $this->__aStoredData = array_merge($storedData, $aFormData);
                        $this->bHasEdited = true;
                        $this->_processAfterEdition($this->_getStoredData());
                    } else {
                        // creating data

                        $aFormData = $this->_processBeforeCreation($aFormData);
                        if (is_array($aFormData) && 0 !== count($aFormData)) {
                            if ($this->i18n()) {
                                $this->oForm->_debug(
                                    '',
                                    'DB insert, taking care of sys_language_uid '.$this->i18n_getSysLanguageUid()
                                );
                                $aFormData['sys_language_uid'] = $this->i18n_getSysLanguageUid();
                            }

                            if ($this->fillStandardTYPO3fields()) {
                                if (!array_key_exists('pid', $aFormData)) {
                                    $aFormData['pid'] = $GLOBALS['TSFE']->id;
                                }

                                if (!array_key_exists('cruser_id', $aFormData)) {
                                    $aFormData['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
                                }

                                if (!array_key_exists('crdate', $aFormData)) {
                                    $aFormData['crdate'] = $GLOBALS['EXEC_TIME'];
                                }

                                if (!array_key_exists('tstamp', $aFormData)) {
                                    $aFormData['tstamp'] = $GLOBALS['EXEC_TIME'];
                                }
                            }

                            $this->oForm->_debug($aFormData, 'EXECUTION OF DATAHANDLER DB - INSERTION MODE in '.$tablename);

                            $this->newEntryId = \Sys25\RnBase\Database\Connection::getInstance()->doInsert($tablename, $aFormData);

                            $this->oForm->_debug(
                                \Sys25\RnBase\Database\Connection::getInstance()->getDatabaseConnection()->debug_lastBuiltQuery,
                                'DATAHANDLER DB - SQL EXECUTED'
                            );
                            $this->oForm->_debug('', 'NEW ENTRY ID ['.$keyname.'='.$this->newEntryId.']');

                            $this->bHasCreated = true;

                            // updating stored data
                            $this->__aStoredData = [];
                            $this->_getStoredData();
                        } else {
                            $this->newEntryId = false;
                            $this->oForm->_debug('', 'NOTHING CREATED IN DB');

                            // updating stored data
                            $this->__aStoredData = [];
                        }

                        $this->_processAfterCreation($this->_getStoredData());
                    }
                } else {
                    $this->oForm->_debug('', 'EXECUTION OF DATAHANDLER DB - NOTHING TO DO - SKIPPING PROCESS '.$tablename);
                }

                /*   /process/afterinsertion */
                $this->_processAfterInsertion($this->_getStoredData());
            }
        } else {
            $this->oForm->mayday(
                "DATAHANDLER configuration isn't correct : check /tablename AND /keyname in your datahandler conf"
            );
        }
    }

    /**
     * Remove all non TCA field from fromData. Can be disabled by cleanNonTcaFields="false".
     *
     * @param array $aFormData
     *
     * @return array cleaned data array
     */
    protected function cleanNonTcaFields($aFormData)
    {
        if ($this->_defaultTrue('/cleannontcafields')) {
            $tablename = $this->tableName();
            $cols = \Sys25\RnBase\Backend\Utility\TCA::getTcaColumns($tablename);
            if (!empty($cols)) {
                $cols = array_keys($cols);
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getCrdateFieldForTable($tablename)) {
                    $cols[] = $field;
                }
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getTstampFieldForTable($tablename)) {
                    $cols[] = $field;
                }
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getDeletedFieldForTable($tablename)) {
                    $cols[] = $field;
                }
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getLanguageFieldForTable($tablename)) {
                    $cols[] = $field;
                }
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getTransOrigPointerFieldForTable($tablename)) {
                    $cols[] = $field;
                }
                if ($field = \Sys25\RnBase\Backend\Utility\TCA::getSortbyFieldForTable($tablename)) {
                    $cols[] = $field;
                }
            }
            if (!empty($cols)) {
                $cols[] = 'pid';
                $aFormData = \Sys25\RnBase\Utility\Arrays::removeNotIn($aFormData, $cols);
            }
        }

        return $aFormData;
    }

    /**
     * Look up all data for database from widgets.
     *
     * @return multitype:NULL
     */
    protected function getDataPreparedForDB()
    {
        $aRes = [];
        $aKeys = array_keys($this->oForm->aORenderlets);
        reset($aKeys);

        foreach ($aKeys as $sAbsName) {
            if (!$this->oForm->aORenderlets[$sAbsName]->_renderOnly()
                && $this->oForm->aORenderlets[$sAbsName]->isSaveable()
                && (!$this->oForm->aORenderlets[$sAbsName]->maySubmit()
                    || $this->oForm->aORenderlets[$sAbsName]->hasBeenDeeplySubmitted())
            ) {
                $sFlatName = $this->oForm->aORenderlets[$sAbsName]->getName();
                $aRes[$sFlatName] = $this->oForm->aORenderlets[$sAbsName]->_flatten(
                    $this->oForm->aORenderlets[$sAbsName]->getValue()
                );
            }
        }

        reset($aRes);

        return $aRes;
    }

    /**
     * called before any database insert or update.
     *
     * @param array $aData
     */
    protected function _processBeforeInsertion($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/beforeinsertion/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $aData = $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }

            if (!is_array($aData)) {
                $aData = [];
            }
        }

        reset($aData);

        return $aData;
    }

    /**
     * called after any database insert or update.
     *
     * @param array $aData
     */
    protected function _processAfterInsertion($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/afterinsertion/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }
        }
    }

    /**
     * called before insert data into database.
     *
     * @param array $aData
     *
     * @return array
     */
    protected function _processBeforeCreation($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/beforecreation/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $aData = $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }

            if (!is_array($aData)) {
                $aData = [];
            }
        }

        reset($aData);

        return $aData;
    }

    /**
     * called before insert data into database.
     *
     * @param array $aData
     *
     * @return array
     */
    protected function _processAfterCreation($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/aftercreation/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }
        }
    }

    protected function _processBeforeEdition($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/beforeedition/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $aData = $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }

            if (!is_array($aData)) {
                $aData = [];
            }
        }

        reset($aData);

        return $aData;
    }

    protected function _processAfterEdition($aData)
    {
        if (false !== ($aUserObj = $this->getConfigValue('/process/afteredition/'))) {
            if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
                $this->getForm()->getRunnable()->callRunnable(
                    $aUserObj,
                    $aData
                );
            }
        }
    }

    public function _edition()
    {
        if ($this->_isClearSubmitted()) {
            // clearsubmitted should display a blank-data page
            // except if edition or new i18n requested

            if ($this->oForm->editionRequested() || $this->newI18nRequested()) {
                return true;
            }

            return false;
        }

        return false !== $this->_currentEntryId();
    }

    protected function __getDbData($sTablename, $sKeyname, $iUid, $sFields = '*')
    {
        $options = [];
        $options['enablefieldsoff'] = 1;
        $options['where'] = $sKeyname.' = '.\Sys25\RnBase\Database\Connection::getInstance()->fullQuoteStr($iUid, $sTablename);
        $ret = \Sys25\RnBase\Database\Connection::getInstance()->doSelect($sFields, $sTablename, $options, 0);

        return count($ret) ? $ret[0] : false;
    }

    public function _getStoredData($sName = false)
    {
        if (empty($this->__aStoredData)) {
            // Hier wird der Record aus der DB geholt
            $this->__aStoredData = [];

            $tablename = $this->tableName();
            $keyname = $this->keyName();

            $editid = $this->_currentEntryId();

            if (false !== $editid) {
                if (false !== ($this->__aStoredData = $this->__getDbData($tablename, $keyname, $editid))) {
                    // on va rechercher la configuration du champ en question
                    reset($this->oForm->aORenderlets);
                    $aRdts = array_keys($this->oForm->aORenderlets);
                    foreach ($aRdts as $fieldName) {
                        // Das ist nur für Confirm-Felder interessant
                        if (false !== ($sConfirm = $this->oForm->aORenderlets[$fieldName]->_navConf('/confirm'))) {
                            $this->__aStoredData[$fieldName] = $this->__aStoredData[$sConfirm];
                        }
                    }
                } else {
                    $this->oForm->mayday('DATAHANDLER DB : EDITION OF ENTRY '.$editid.' FAILED');
                }
            }
        }

        if (is_array($this->__aStoredData)) {
            if (false !== $sName) {
                if (array_key_exists($sName, $this->__aStoredData)) {
                    return $this->__aStoredData[$sName];
                } else {
                    return '';
                }
            }

            reset($this->__aStoredData);

            return $this->__aStoredData;
        }

        return (false !== $sName) ? '' : [];
    }

    public function newI18nRequested()
    {
        if (false !== $this->oForm->aAddPostVars) {
            reset($this->oForm->aAddPostVars);
            foreach ($this->oForm->aAddPostVars as $sKey => $notNeeded) {
                if (array_key_exists('action', $this->oForm->aAddPostVars[$sKey])
                    && 'requestNewI18n' === $this->oForm->aAddPostVars[$sKey]['action']
                ) {
                    if ($this->tableName() === $this->oForm->aAddPostVars[$sKey]['params']['tablename'] && $this->tableName()) {
                        $sOurSafeLock = $this->oForm->_getSafeLock(
                            'requestNewI18n'.':'.$this->oForm->aAddPostVars[$sKey]['params']['tablename'].':'
                            .$this->oForm->aAddPostVars[$sKey]['params']['recorduid'].':'
                            .$this->oForm->aAddPostVars[$sKey]['params']['languid']
                        );
                        $sTheirSafeLock = $this->oForm->aAddPostVars[$sKey]['params']['hash'];
                        if ($sOurSafeLock === $sTheirSafeLock) {
                            return [
                                'i18n_parent' => $this->oForm->aAddPostVars[$sKey]['params']['recorduid'],
                                'sys_language_uid' => $this->oForm->aAddPostVars[$sKey]['params']['languid'],
                            ];
                        }
                    }
                }
            }
        }

        return false;
    }

    public function i18n_updateChildsOnSave()
    {
        return $this->_defaultFalse('/i18n/update_childs_on_save/');
    }

    public function i18n_currentRecordUsesDefaultLang()
    {
        return 0 === (int) $this->_getStoredData('sys_language_uid');
    }

    public function i18n_currentRecordUsesLang()
    {
        // receives unknown number of arguments like 0, -1

        $aParams = func_get_args();

        return in_array(
            (int) $this->_getStoredData('sys_language_uid'),
            $aParams
        );
    }

    public function i18n_getStoredParent($bStrict = false)
    {
        if ($this->i18n() && $this->_edition()) {
            if (false === $this->__aStoredI18NParent) {
                $aData = $this->_getStoredData();

                if ($this->i18n_currentRecordUsesDefaultLang()) {
                    if (true === $bStrict) {
                        return false;
                    }

                    $this->__aStoredI18NParent = $aData;
                } else {
                    $iParent = (int) $aData['l18n_parent'];
                    if (false !== ($aParent = $this->__getDbData($this->tableName(), $this->keyName(), $iParent))) {
                        $this->__aStoredI18NParent = $aParent;
                    }
                }
            }
        }

        return $this->__aStoredI18NParent;
    }

    public function i18n_getThisStoredParent($sField, $bStrict = false)
    {
        $aParent = $this->i18n_getStoredParent($bStrict);
        if (array_key_exists($sField, $aParent)) {
            return $aParent[$sField];
        }

        return false;
    }

    protected function fillStandardTYPO3fields()
    {
        return $this->_isTrue('/fillstandardtypo3fields');
    }
}

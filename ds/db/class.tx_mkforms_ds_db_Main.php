<?php

/**
 * Plugin 'ds_db' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_db_Main extends formidable_maindatasource
{
    public $oDb = false;

    public $sTable = false;

    public $sKey = false;

    public function initDataSet($sKey)
    {
        if (($this->sTable = $this->_navConf('/table')) === false) {
            $this->oForm->mayday("datasource:DB[name='" . $this->getName() . "'] You have to provide <b>/table</b>.");
        }

        if (($this->sKey = $this->_navConf('/key')) === false) {
            $this->sKey = 'uid';
        }

        $this->initDb();

        $oDataSet = tx_rnbase::makeInstance('formidable_maindataset');

        if ($sKey === 'new') {
            // new record to create
            $oDataSet->initFloating($this);
        } else {
            // existing record to grab

            $rSql = $this->oForm->_watchOutDB(
                $this->oDb->exec_SELECTquery(
                    '*',
                    $this->sTable,
                    $this->sKey . "='" . $sKey . "'"
                )
            );

            if (($aDataSet = $this->oDb->sql_fetch_assoc($rSql)) !== false) {
                $oDataSet->initAnchored(
                    $this,
                    $aDataSet,
                    $sKey
                );
            } else {
                if ($this->defaultFalse('/fallbacktonew') === true) {
                    // fallback new record to create
                    $oDataSet->initFloating($this);
                } else {
                    $this->oForm->mayday(
                        "datasource:DB[name='" . $this->getName() . "'] No dataset matching " . $this->sKey . "='" . $sKey
                        . "' was found."
                    );
                }
            }
        }

        $sSignature = $oDataSet->getSignature();
        $this->aODataSets[$sSignature] =& $oDataSet;

        return $sSignature;
    }

    public function &_fetchData($aConfig = array(), $aFilters = array())
    {
        $this->initDb();

        $iNumRows = 0;
        $aResults = array();

        $aFilters = $this->beforeSqlFilter($aConfig, $aFilters);

        if (($sSql = $this->_getSql($aConfig, $aFilters)) !== false) {
            $this->oForm->_debug(
                $sSql,
                'DATASOURCE:DB [' . $this->aElement['name'] . ']'
            );

            $sSql = $this->beforeSqlExec($sSql, $aConfig, $aFilters);

            $rSql = $this->oForm->_watchOutDB(
                $this->oDb->sql_query($sSql),
                $sSql
            );

            if ($rSql) {
                $iNumRows = $this->_getTotalNumberOfRows();

                while (($aRs =& $this->oDb->sql_fetch_assoc($rSql)) !== false) {
                    $aResults[] = $aRs;
                    unset($aRs);
                }
            }
        }

        return array(
            'numrows' => $iNumRows,
            'results' => &$aResults,
        );
    }

    public function initDb()
    {
        if ($this->oDb === false) {
            if (($aLink = $this->_navConf('/link')) !== false) {
                $this->oDb = Tx_Rnbase_Database_Connection::getInstance()->getDatabaseConnection();

                if ($this->oForm->isRunneable(($sHost = $aLink['host']))) {
                    $sHost = $this->callRunneable($sHost);
                }

                if ($this->oForm->isRunneable(($sUser = $aLink['user']))) {
                    $sUser = $this->callRunneable($sUser);
                }

                if ($this->oForm->isRunneable(($sPassword = $aLink['password']))) {
                    $sPassword = $this->callRunneable($sPassword);
                }

                if ($this->oForm->isRunneable(($sDbName = $aLink['dbname']))) {
                    $sDbName = $this->callRunneable($sDbName);
                }

                $this->oDb->sql_pconnect($sHost, $sUser, $sPassword);
                $this->oDb->sql_select_db($sDbName);
            } else {
                $this->oDb =& $GLOBALS['TYPO3_DB'];
            }
        }
    }

    public function baseCleanBeforeSession()
    {
        parent::baseCleanBeforeSession();
        unset($this->oDb);
        $this->oDb = false;
    }

    public function beforeSqlExec($sSql, $aConfig, $aFilters)
    {
        if (($mUserobj = $this->_navConf('/beforesqlexec')) !== false) {
            if ($this->oForm->isRunneable($mUserobj)) {
                $sSql = $this->callRunneable(
                    $mUserobj,
                    array(
                        'sql' => $sSql,
                        'config' => $aConfig,
                        'filters' => $aFilters
                    )
                );
            }
        }

        return $sSql;
    }

    public function beforeSqlFilter($aConfig, $aFilters)
    {
        if (($mUserobj = $this->_navConf('/beforesqlfilter')) !== false) {
            if ($this->oForm->isRunneable($mUserobj)) {
                $aFilters = $this->callRunneable(
                    $mUserobj,
                    array(
                        'config' => $aConfig,
                        'filters' => $aFilters
                    )
                );
            }
        }

        return $aFilters;
    }

    public function _getSql($aConfig = array(), $aFilters = array())
    {
        $sSqlFilters = '';
        $sSqlOrderBy = '';

        if ($this->_isFalse('/sql') && $this->_isFalse('/table')) {
            return false;
        }

        if (($sTable = $this->_navConf('/table')) !== false) {
            $sSqlBase = 'SELECT ';
            if (($sFields = $this->_navConf('/fields')) !== false) {
                $sSqlBase .= $sFields . ' ';
            } else {
                $sSqlBase .= '* ';
            }
            $sSqlBase .= 'FROM ' . $sTable . ' ';

            if (($aWheres = $this->_navConf('/wheres')) !== false) {
                $sSqlBase .= 'WHERE TRUE ' . $this->_getAdditionalWheres($aWheres);
            }
        } else {
            $sSqlBase = $this->_navConf('/sql');

            if ($this->oForm->isRunneable($sSqlBase)) {
                $sSqlBase = $this->callRunneable($sSqlBase);
            }
        }

        $sSqlBase = trim($sSqlBase);

        if (($mEnableFields = $this->_defaultFalseMixed('/enablefields')) !== false) {
            if ($mEnableFields === true) {
                // we have to determine the table name

                $oParser = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getSqlParserClass());
                $aParsed = $oParser->parseSQL($sSqlBase);

                if (is_array($aParsed) && count($aParsed['FROM']) == 1) {
                    $sTable = $aParsed['FROM'][0]['table'];
                } else {
                    // mayday
                    $this->oForm->mayday(
                        "datasource:DB[name='" . $this->getName()
                        . "'] cannot automatically determine table name for enableFields"
                    );
                }
            } else {
                $sTable = $mEnableFields;
            }

            $sEnableFields = $this->getForm()->getCObj()->enableFields($sTable);
        } else {
            $sEnableFields = '';
        }

        if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr(strtoupper($sSqlBase), 'SELECT')) {
            // modify the SQL query to include SQL_CALC_FOUND_ROWS
            $sSqlBase = 'SELECT SQL_CALC_FOUND_ROWS ' . substr($sSqlBase, strlen('SELECT'));
        } else {
            $this->oForm->mayday(
                'DATASOURCE DB "' . $this->aElement['name'] . '" - requires /sql to start with SELECT. Check your XML conf.'
            );
        }

        if (strpos(strtoupper($sSqlBase), 'WHERE') === false) {
            $sSqlBase .= ' WHERE TRUE ';
        }

        if (!empty($aFilters)) {
            $sSqlFilters = ' AND (' . implode(' AND ', $aFilters) . ')';
        }

        $sSqlFilters .= $sEnableFields;

        if (($sSqlGroupBy = stristr($sSqlBase, 'GROUP BY')) !== false) {
            $sSqlBase = str_replace($sSqlGroupBy, '', $sSqlBase);
        } else {
            $sSqlGroupBy = '';
        }

        if (array_key_exists('sortcolumn', $aConfig) && trim($aConfig['sortcolumn']) != '') {
            $sSqlOrderBy = ' ORDER BY  ' . $aConfig['sortcolumn'] . ' ';

            if (array_key_exists('sortdirection', $aConfig) && trim($aConfig['sortdirection']) != '') {
                if (strtoupper($aConfig['sortdirection']) === 'ASC' || strtoupper($aConfig['sortdirection']) === 'DESC') {
                    $sSqlOrderBy .= ' ' . strtoupper($aConfig['sortdirection']);
                }
            }
        }

        $aLimit = $this->_getRecordWindow(
            $aConfig['page'],
            $aConfig['perpage']
        );

        $sSqlLimit = $aLimit['sql'];

        return $sSqlBase . ' ' . $sSqlFilters . ' ' . $sSqlGroupBy . ' ' . $sSqlOrderBy . ' ' . $sSqlLimit;
    }

    public function _getTotalNumberOfRows()
    {
        return $this->oForm->_navConf(
            '/nbrows',
            $this->oDb->sql_fetch_assoc(
                $this->oForm->_watchOutDB(
                    $this->oDb->sql_query(
                        'SELECT FOUND_ROWS() as nbrows'
                    )
                )
            )
        );
    }

    public function dset_writeDataSet($sSignature)
    {
        if (!array_key_exists($sSignature, $this->aODataSets)) {
            return false;
        }

        if ($this->aODataSets[$sSignature]->isFloating()) {
            if ($this->aODataSets[$sSignature]->needsToBeWritten()) {
                if (($mBefore = $this->_navConf('/beforecreation')) !== false) {
                    if ($this->oForm->isRunneable($mBefore)) {
                        $this->callRunneable(
                            $mBefore,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }

                $aData = $this->aODataSets[$sSignature]->aChangedCells;

                if ($this->defaultTrue('/addsysfields') === true) {
                    $aData['crdate'] = time();
                    $aData['tstamp'] = time();
                }

                $this->oForm->_watchOutDB(
                    $this->oDb->exec_INSERTquery(
                        $this->sTable,
                        $aData
                    )
                );

                $iUid = $this->oDb->sql_insert_id();

                $rSql = $this->oDb->exec_SELECTquery(
                    '*',
                    $this->sTable,
                    $this->sKey . "='" . $iUid . "'"
                );

                if (($aNew = $this->oDb->sql_fetch_assoc($rSql)) !== false) {
                    $this->aODataSets[$sSignature]->initAnchored(
                        $this,
                        $aNew,
                        $iUid
                    );
                }

                if (($mAfter = $this->_navConf('/aftercreation')) !== false) {
                    if ($this->oForm->isRunneable($mAfter)) {
                        $this->callRunneable(
                            $mAfter,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }
            }
        } else {
            if ($this->aODataSets[$sSignature]->needsToBeWritten()) {
                if (($mBefore = $this->_navConf('/beforeedition')) !== false) {
                    if ($this->oForm->isRunneable($mBefore)) {
                        $this->callRunneable(
                            $mBefore,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }

                $aData = $this->aODataSets[$sSignature]->aChangedCells;
                if ($this->defaultTrue('/addsysfields') === true) {
                    $aData['tstamp'] = time();
                }

                $this->oForm->_watchOutDB(
                    $this->oDb->exec_UPDATEquery(
                        $this->sTable,
                        $this->sKey . "='" . $this->aODataSets[$sSignature]->getKey() . "'",
                        $aData
                    )
                );

                if (($mAfter = $this->_navConf('/afteredition')) !== false) {
                    if ($this->oForm->isRunneable($mAfter)) {
                        $this->callRunneable(
                            $mAfter,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }
            }
        }
    }

    public function dset_getSignature()
    {
        die('dsdb:dset_getSignature() disabled');
    }

    public function dset_setCellValue($sSignature, $sPath, $mValue, $sAbsName = false)
    {
        $this->aODataSets[$sSignature]->setCellValue($sPath, $mValue);
    }

    public function dset_hasFlexibleStructure()
    {
        // FALSE as structure may not expand / is not flexible (unlike a flexform)
        return false;
    }

    public function _getAdditionalWheres($aWheres, $sPrefix = '')
    {
        $sTempWhere = '';

        if ($aWheres !== false && is_array($aWheres) && count($aWheres) > 0) {
            $aClauses = array();
            $bClauses = false;

            reset($aWheres);
            while (list($sType, $aWhere) = each($aWheres)) {
                $aTemp = explode('-', $sType);
                $sType = trim(strtoupper($aTemp[0]));
                $bProcess = true;

                if (is_array($aWhere) && array_key_exists('process', $aWhere)) {
                    if ($this->oForm->isRunneable($aWhere['process'])) {
                        $bProcess = $this->callRunneable(
                            $aWhere['process']
                        );
                    } else {
                        if ($this->oForm->_isFalseVal($aWhere['process'])) {
                            $bProcess = false;
                        }
                    }
                }

                if ($bProcess) {
                    switch ($sType) {
                        case 'WHERE': {

                            if (($mProcess = $this->oForm->_defaultTrue('/process', $aWhere)) !== false) {
                                if ($this->oForm->isRunneable($mProcess)) {
                                    $mProcess = $this->callRunneable($mProcess);
                                }
                            }

                            if ($mProcess === true) {
                                if (array_key_exists('value', $aWhere)) {
                                    $mValue = $aWhere['value'];
                                } else {
                                    $mValue = '';
                                }

                                if ($this->oForm->isRunneable($mValue)) {
                                    $mValue = $this->callRunneable($mValue);
                                }

                                if ($mValue == '') {
                                    $mValue = "''";
                                }

                                if ($this->oForm->isRunneable($aWhere['comparison'])) {
                                    $aWhere['comparison'] = $this->callRunneable($aWhere['comparison']);
                                }

                                $sComparison = strtoupper(trim($aWhere['comparison']));

                                if ($bClauses && (($mLogic = $this->oForm->_navConf('/logic', $aWhere)) !== false)) {
                                    if ($this->oForm->isRunneable($mLogic)) {
                                        $mLogic = $this->callRunneable($mLogic);
                                    }

                                    $aClauses[] = (in_array(trim(strtoupper($mLogic)), array('AND', 'OR'))) ? trim(
                                        strtoupper($mLogic)
                                    ) : 'AND';
                                }

                                $aClauses[] = ' ' . $sPrefix . $aWhere['term'] . ' ' . $sComparison . (($sComparison == 'IN'
                                        || $sComparison == 'NOT IN') ? ' (' : " '") . $mValue . (($sComparison == 'IN'
                                        || $sComparison == 'NOT IN') ? ') ' : "'");
                                $bClauses = true;
                                break;
                            }
                        }
                        case 'BEGINBRACE': {
                            $aClauses[] = '(';
                            break;
                        }
                        case 'ENDBRACE': {
                            $aClauses[] = ')';
                            break;
                        }
                        case 'LOGIC': {

                            if ($bClauses) {
                                if (is_array($aWhere) && array_key_exists('value', $aWhere)) {
                                    $mValue = $aWhere['value'];
                                } else {
                                    $mValue = $aWhere;
                                }

                                if ($this->oForm->isRunneable($mValue)) {
                                    $mValue = $this->callRunneable($mValue);
                                }

                                $aClauses[] = (in_array(trim(strtoupper($mValue)), array('AND', 'OR'))) ? trim(
                                    strtoupper($mValue)
                                ) : '';
                            }

                            break;
                        }
                    }
                }
            }

            $sTempWhere = implode(' ', $aClauses);
        }

        if ($bClauses && trim($sTempWhere) != '') {
            return ' AND (' . $sTempWhere . ')';
        }

        return '';
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/db/class.tx_mkforms_ds_db_Main.php']
) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/db/class.tx_mkforms_ds_db_Main.php']);
}

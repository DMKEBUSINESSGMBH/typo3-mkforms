<?php

/**
 * Plugin 'ds_db' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_db_Main extends formidable_maindatasource
{
    /**
     * @var \Sys25\RnBase\Database\Connection
     */
    public $oDb = false;

    public $sTable = false;

    public $sKey = false;

    public function initDataSet($sKey)
    {
        if (false === ($this->sTable = $this->_navConf('/table'))) {
            $this->oForm->mayday("datasource:DB[name='".$this->getName()."'] You have to provide <b>/table</b>.");
        }

        if (false === ($this->sKey = $this->_navConf('/key'))) {
            $this->sKey = 'uid';
        }

        $this->initDb();

        $oDataSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_maindataset');

        if ('new' === $sKey) {
            // new record to create
            $oDataSet->initFloating($this);
        } else {
            // existing record to grab
            $aDataSet = \Sys25\RnBase\Database\Connection::getInstance()->doSelect(
                '*',
                $this->sTable,
                ['where' => $this->sKey."='".$sKey."'"]
            );

            if (false !== $aDataSet) {
                $oDataSet->initAnchored(
                    $this,
                    $aDataSet,
                    $sKey
                );
            } else {
                if (true === $this->defaultFalse('/fallbacktonew')) {
                    // fallback new record to create
                    $oDataSet->initFloating($this);
                } else {
                    $this->oForm->mayday(
                        "datasource:DB[name='".$this->getName()."'] No dataset matching ".$this->sKey."='".$sKey
                        ."' was found."
                    );
                }
            }
        }

        $sSignature = $oDataSet->getSignature();
        $this->aODataSets[$sSignature] = &$oDataSet;

        return $sSignature;
    }

    public function &_fetchData($aConfig = [], $aFilters = [])
    {
        $this->initDb();

        $iNumRows = 0;
        $aResults = [];

        $aFilters = $this->beforeSqlFilter($aConfig, $aFilters);

        if (false !== ($sSql = $this->_getSql($aConfig, $aFilters))) {
            $this->oForm->_debug(
                $sSql,
                'DATASOURCE:DB ['.$this->aElement['name'].']'
            );

            $sSql = $this->beforeSqlExec($sSql, $aConfig, $aFilters);

            $rows = \Sys25\RnBase\Database\Connection::getInstance()->doQuery($sSql);

            if ($rows) {
                $iNumRows = $rows->num_rows;

                foreach ($rows as $aRs) {
                    $aResults[] = $aRs;
                    unset($aRs);
                }
            }
        }

        return [
            'numrows' => $iNumRows,
            'results' => &$aResults,
        ];
    }

    public function initDb()
    {
        if (false === $this->oDb) {
            if (false !== ($aLink = $this->_navConf('/link'))) {
                $this->oDb = \Sys25\RnBase\Database\Connection::getInstance()->getDatabaseConnection();

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

                throw new RuntimeException('Make connection to other database possible again');
                $this->oDb->sql_pconnect($sHost, $sUser, $sPassword);
                $this->oDb->sql_select_db($sDbName);
            } else {
                $this->oDb = &\Sys25\RnBase\Database\Connection::getInstance();
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
        if (false !== ($mUserobj = $this->_navConf('/beforesqlexec'))) {
            if ($this->oForm->isRunneable($mUserobj)) {
                $sSql = $this->callRunneable(
                    $mUserobj,
                    [
                        'sql' => $sSql,
                        'config' => $aConfig,
                        'filters' => $aFilters,
                    ]
                );
            }
        }

        return $sSql;
    }

    public function beforeSqlFilter($aConfig, $aFilters)
    {
        if (false !== ($mUserobj = $this->_navConf('/beforesqlfilter'))) {
            if ($this->oForm->isRunneable($mUserobj)) {
                $aFilters = $this->callRunneable(
                    $mUserobj,
                    [
                        'config' => $aConfig,
                        'filters' => $aFilters,
                    ]
                );
            }
        }

        return $aFilters;
    }

    public function _getSql($aConfig = [], $aFilters = [])
    {
        $sSqlFilters = '';
        $sSqlOrderBy = '';

        if ($this->_isFalse('/sql') && $this->_isFalse('/table')) {
            return false;
        }

        if (false !== ($sTable = $this->_navConf('/table'))) {
            $sSqlBase = 'SELECT ';
            if (false !== ($sFields = $this->_navConf('/fields'))) {
                $sSqlBase .= $sFields.' ';
            } else {
                $sSqlBase .= '* ';
            }
            $sSqlBase .= 'FROM '.$sTable.' ';

            if (false !== ($aWheres = $this->_navConf('/wheres'))) {
                $sSqlBase .= 'WHERE TRUE '.$this->_getAdditionalWheres($aWheres);
            }
        } else {
            $sSqlBase = $this->_navConf('/sql');

            if ($this->oForm->isRunneable($sSqlBase)) {
                $sSqlBase = $this->callRunneable($sSqlBase);
            }
        }

        $sSqlBase = trim($sSqlBase);

        if (false !== ($mEnableFields = $this->_defaultFalseMixed('/enablefields'))) {
            if (true === $mEnableFields) {
                // we have to determine the table name

                $oParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getSqlParserClass());
                $aParsed = $oParser->parseSQL($sSqlBase);

                if (is_array($aParsed) && 1 == count($aParsed['FROM'])) {
                    $sTable = $aParsed['FROM'][0]['table'];
                } else {
                    // mayday
                    $this->oForm->mayday(
                        "datasource:DB[name='".$this->getName()
                        ."'] cannot automatically determine table name for enableFields"
                    );
                }
            } else {
                $sTable = $mEnableFields;
            }
            $sEnableFields = $this->oDb->enableFields($sTable);
        } else {
            $sEnableFields = '';
        }

        if (\Sys25\RnBase\Utility\Strings::isFirstPartOfStr(strtoupper($sSqlBase), 'SELECT')) {
            // modify the SQL query to include SQL_CALC_FOUND_ROWS
            $sSqlBase = 'SELECT SQL_CALC_FOUND_ROWS '.substr($sSqlBase, strlen('SELECT'));
        } else {
            $this->oForm->mayday(
                'DATASOURCE DB "'.$this->aElement['name'].'" - requires /sql to start with SELECT. Check your XML conf.'
            );
        }

        if (false === strpos(strtoupper($sSqlBase), 'WHERE')) {
            $sSqlBase .= ' WHERE TRUE ';
        }

        if (!empty($aFilters)) {
            $sSqlFilters = ' AND ('.implode(' AND ', $aFilters).')';
        }

        $sSqlFilters .= $sEnableFields;

        if (false !== ($sSqlGroupBy = stristr($sSqlBase, 'GROUP BY'))) {
            $sSqlBase = str_replace($sSqlGroupBy, '', $sSqlBase);
        } else {
            $sSqlGroupBy = '';
        }

        if (array_key_exists('sortcolumn', $aConfig) && '' != trim($aConfig['sortcolumn'])) {
            $sSqlOrderBy = ' ORDER BY  '.$aConfig['sortcolumn'].' ';

            if (array_key_exists('sortdirection', $aConfig) && '' != trim($aConfig['sortdirection'])) {
                if ('ASC' === strtoupper($aConfig['sortdirection']) || 'DESC' === strtoupper($aConfig['sortdirection'])) {
                    $sSqlOrderBy .= ' '.strtoupper($aConfig['sortdirection']);
                }
            }
        }

        $aLimit = $this->_getRecordWindow(
            $aConfig['page'],
            $aConfig['perpage']
        );

        $sSqlLimit = $aLimit['sql'];

        return $sSqlBase.' '.$sSqlFilters.' '.$sSqlGroupBy.' '.$sSqlOrderBy.' '.$sSqlLimit;
    }

    public function _getTotalNumberOfRows()
    {
        throw new RuntimeException('This method is no longer available. Please just count the retrieved rows.');
    }

    public function dset_writeDataSet($sSignature)
    {
        if (!array_key_exists($sSignature, $this->aODataSets)) {
            return false;
        }

        if ($this->aODataSets[$sSignature]->isFloating()) {
            if ($this->aODataSets[$sSignature]->needsToBeWritten()) {
                if (false !== ($mBefore = $this->_navConf('/beforecreation'))) {
                    if ($this->oForm->isRunneable($mBefore)) {
                        $this->callRunneable(
                            $mBefore,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }

                $aData = $this->aODataSets[$sSignature]->aChangedCells;

                if (true === $this->defaultTrue('/addsysfields')) {
                    $aData['crdate'] = $GLOBALS['EXEC_TIME'];
                    $aData['tstamp'] = $GLOBALS['EXEC_TIME'];
                }

                $iUid = $this->oDb->doInsert($this->sTable, $aData);
                $rows = $this->oDb->doSelect(
                    '*',
                    $this->sTable,
                    ['where' => $this->sKey."='".$iUid."'"]
                );

                if ($rows) {
                    $this->aODataSets[$sSignature]->initAnchored(
                        $this,
                        $rows[0],
                        $iUid
                    );
                }

                if (false !== ($mAfter = $this->_navConf('/aftercreation'))) {
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
                if (false !== ($mBefore = $this->_navConf('/beforeedition'))) {
                    if ($this->oForm->isRunneable($mBefore)) {
                        $this->callRunneable(
                            $mBefore,
                            $this->aODataSets[$sSignature]->getDataSet(),
                            $this->aODataSets[$sSignature]
                        );
                    }
                }

                $aData = $this->aODataSets[$sSignature]->aChangedCells;
                if (true === $this->defaultTrue('/addsysfields')) {
                    $aData['tstamp'] = $GLOBALS['EXEC_TIME'];
                }

                $this->oDb->doUpdate(
                    $this->sTable,
                    $this->sKey."='".$this->aODataSets[$sSignature]->getKey()."'",
                    $aData
                );

                if (false !== ($mAfter = $this->_navConf('/afteredition'))) {
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
        exit('dsdb:dset_getSignature() disabled');
    }

    public function dset_hasFlexibleStructure()
    {
        // FALSE as structure may not expand / is not flexible (unlike a flexform)
        return false;
    }

    public function _getAdditionalWheres($aWheres, $sPrefix = '')
    {
        $sTempWhere = '';

        if (false !== $aWheres && is_array($aWheres) && count($aWheres) > 0) {
            $aClauses = [];
            $bClauses = false;

            reset($aWheres);
            foreach ($aWheres as $sType => $aWhere) {
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
                        case 'WHERE':
                            if (false !== ($mProcess = $this->oForm->_defaultTrue('/process', $aWhere))) {
                                if ($this->oForm->isRunneable($mProcess)) {
                                    $mProcess = $this->callRunneable($mProcess);
                                }
                            }

                            if (true === $mProcess) {
                                if (array_key_exists('value', $aWhere)) {
                                    $mValue = $aWhere['value'];
                                } else {
                                    $mValue = '';
                                }

                                if ($this->oForm->isRunneable($mValue)) {
                                    $mValue = $this->callRunneable($mValue);
                                }

                                if ('' == $mValue) {
                                    $mValue = "''";
                                }

                                if ($this->oForm->isRunneable($aWhere['comparison'])) {
                                    $aWhere['comparison'] = $this->callRunneable($aWhere['comparison']);
                                }

                                $sComparison = strtoupper(trim($aWhere['comparison']));

                                if ($bClauses && (false !== ($mLogic = $this->oForm->_navConf('/logic', $aWhere)))) {
                                    if ($this->oForm->isRunneable($mLogic)) {
                                        $mLogic = $this->callRunneable($mLogic);
                                    }

                                    $aClauses[] = (in_array(trim(strtoupper($mLogic)), ['AND', 'OR'])) ? trim(
                                        strtoupper($mLogic)
                                    ) : 'AND';
                                }

                                $aClauses[] = ' '.$sPrefix.$aWhere['term'].' '.$sComparison.(('IN' == $sComparison
                                        || 'NOT IN' == $sComparison) ? ' (' : " '").$mValue.(('IN' == $sComparison
                                        || 'NOT IN' == $sComparison) ? ') ' : "'");
                                $bClauses = true;
                                break;
                            }

                            // no break
                        case 'BEGINBRACE':
                            $aClauses[] = '(';
                            break;

                        case 'ENDBRACE':
                            $aClauses[] = ')';
                            break;

                        case 'LOGIC':
                            if ($bClauses) {
                                if (is_array($aWhere) && array_key_exists('value', $aWhere)) {
                                    $mValue = $aWhere['value'];
                                } else {
                                    $mValue = $aWhere;
                                }

                                if ($this->oForm->isRunneable($mValue)) {
                                    $mValue = $this->callRunneable($mValue);
                                }

                                $aClauses[] = (in_array(trim(strtoupper($mValue)), ['AND', 'OR'])) ? trim(
                                    strtoupper($mValue)
                                ) : '';
                            }

                            break;
                    }
                }
            }

            $sTempWhere = implode(' ', $aClauses);
        }

        if ($bClauses && '' != trim($sTempWhere)) {
            return ' AND ('.$sTempWhere.')';
        }

        return '';
    }
}

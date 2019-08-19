<?php
/**
 * Plugin 'va_std' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_validator_db_Main extends formidable_mainvalidator
{
    public function validate(&$oRdt)
    {
        $sAbsName = $oRdt->getAbsName();
        $mValue = $oRdt->getValue();

        $aKeys = array_keys($this->_navConf('/'));
        reset($aKeys);
        foreach ($aKeys as $sKey) {
            // Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
            if ($oRdt->hasError() || !$this->canValidate($oRdt, $sKey, $mValue)) {
                break;
            }

            /***********************************************************************
            *
            *	/unique
            *
            ***********************************************************************/

            if ($sKey{0} === 'u' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'unique')) {
                // field value has to be unique in the database
                // checking this

                if (!$this->_isUnique($oRdt, $mValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'DB:unique',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'))
                    );

                    break;
                }
            } /***********************************************************************
            *
            *	/sameasinitial
            *
            ***********************************************************************/

            elseif ($sKey{0} === 'd' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'differsfromdb')) {
                // field value has to differ from the one in the database
                // checking this

                if (!$this->_differsFromDBValue($oRdt, $mValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'DB:differsfromdb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'))
                    );

                    break;
                }
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see formidable_mainvalidator::_isUnique()
     */
    public function _isUnique(&$oRdt, $value)
    {
        if (($sTable = $this->_navConf('/unique/tablename')) !== false) {
            if (($sField = $this->_navConf('/unique/field')) === false) {
                $sField = $oRdt->getName();
            }
            $aDhConf = $this->oForm->_navConf('/control/datahandler/');
            $sKey = $aDhConf['keyname'];
        } else {
            if ($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === 'DB')) {
                $sKey = $oRdt->oDataBridge->oDataSource->sKey;
                $sTable = $oRdt->oDataBridge->oDataSource->sTable;
                $sField = $oRdt->dbridged_mapPath();
            } else {
                $aDhConf = $this->oForm->_navConf('/control/datahandler/');
                $sKey = $aDhConf['keyname'];
                $sTable = $aDhConf['tablename'];
                $sField = $oRdt->getName();
            }
        }

        $value = addslashes($value);

        $where = array();
        $where[] = $sField . ' = ' . Tx_Rnbase_Database_Connection::getInstance()->fullQuoteStr($value, '');

        if ($this->_defaultFalse('/unique/deleted/') === true) {
            $field = tx_rnbase_util_TCA::getDeletedFieldForTable($sTable);
            $field = empty($field) ? 'deleted' : $field;
            $where[] = $field . ' != 1';
        }
        if ($this->_defaultFalse('/unique/disabled/') === true) {
            $field = tx_rnbase_util_TCA::getDisabledFieldForTable($sTable);
            $field = empty($field) ? 'hidden' : $field;
            $where[] = $field . ' != 1';
        }

        $datahandler = $this->getForm()->getDataHandler();
        if ($datahandler->_edition() && !$this->_defaultFalse('/unique/skipedition/')) {
            $where[] = $sKey . ' != \'' . $datahandler->currentId() . '\'';
        }

        $rs = Tx_Rnbase_Database_Connection::getInstance()->doSelect(
            'count(*) as nbentries',
            $sTable,
            array(
                'enablefieldsoff' => $this->_defaultTrue('/unique/enablefieldsoff/'),
                'where' => implode(' AND ', $where),
            )
        );

        return !((int) $rs[0]['nbentries'] > 0);
    }

    /**
     * Checks if the submitted value differs from the one in the DB
     * @param $oRdt
     * @param $value
     */
    public function _differsFromDBValue(&$oRdt, $value)
    {
        $sDeleted = '';

        if (($sTable = $this->_navConf('/differsfromdb/tablename')) !== false) {
            if (($sField = $this->_navConf('/differsfromdb/field')) === false) {
                $sField = $oRdt->getName();
            }

            $aDhConf = $this->oForm->_navConf('/control/datahandler/');
            $sKey = $aDhConf['keyname'];
        } else {
            if ($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === 'DB')) {
                $sKey = $oRdt->oDataBridge->oDataSource->sKey;
                $sTable = $oRdt->oDataBridge->oDataSource->sTable;
                $sField = $oRdt->dbridged_mapPath();
            } else {
                $aDhConf = $this->oForm->_navConf('/control/datahandler/');
                $sKey = $aDhConf['keyname'];
                $sTable = $aDhConf['tablename'];
                $sField = $oRdt->getName();
            }
        }

        if ($this->_defaultFalse('/differsfromdb/deleted/') === true) {
            $sDeleted = ' AND deleted != 1';
        }

        $value = addslashes($value);

        $sWhere = $sField . ' = \'' . $value . '\' AND ' . $sKey . " = '" . $this->oForm->oDataHandler->_currentEntryId() . "'" . $sDeleted;

        $sSql = $GLOBALS['TYPO3_DB']->SELECTquery(
            'count(*) as nbentries',
            $sTable,
            $sWhere
        );

        $rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc(
            $this->oForm->_watchOutDB($GLOBALS['TYPO3_DB']->sql_query($sSql), $sSql)
        );

        return !($rs['nbentries'] > 0);
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php']);
}

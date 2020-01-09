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

            if ('u' === $sKey[0] && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'unique')) {
                // field value has to be unique in the database
                // checking this

                if (!$this->_isUnique($oRdt, $mValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'DB:unique',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message/'))
                    );

                    break;
                }
            } /***********************************************************************
            *
            *	/sameasinitial
            *
            ***********************************************************************/

            elseif ('d' === $sKey[0] && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'differsfromdb')) {
                // field value has to differ from the one in the database
                // checking this

                if (!$this->_differsFromDBValue($oRdt, $mValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'DB:differsfromdb',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message/'))
                    );

                    break;
                }
            }
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see formidable_mainvalidator::_isUnique()
     */
    public function _isUnique(&$oRdt, $value)
    {
        if (false !== ($sTable = $this->_navConf('/unique/tablename'))) {
            if (false === ($sField = $this->_navConf('/unique/field'))) {
                $sField = $oRdt->getName();
            }
            $aDhConf = $this->oForm->_navConf('/control/datahandler/');
            $sKey = $aDhConf['keyname'];
        } else {
            if ($oRdt->hasDataBridge() && ('DB' === $oRdt->oDataBridge->oDataSource->_getType())) {
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

        $where = [];
        $where[] = $sField.' = '.Tx_Rnbase_Database_Connection::getInstance()->fullQuoteStr($value, '');

        if (true === $this->_defaultFalse('/unique/deleted/')) {
            $field = tx_rnbase_util_TCA::getDeletedFieldForTable($sTable);
            $field = empty($field) ? 'deleted' : $field;
            $where[] = $field.' != 1';
        }
        if (true === $this->_defaultFalse('/unique/disabled/')) {
            $field = tx_rnbase_util_TCA::getDisabledFieldForTable($sTable);
            $field = empty($field) ? 'hidden' : $field;
            $where[] = $field.' != 1';
        }

        $datahandler = $this->getForm()->getDataHandler();
        if ($datahandler->_edition() && !$this->_defaultFalse('/unique/skipedition/')) {
            $where[] = $sKey.' != \''.$datahandler->currentId().'\'';
        }

        $rs = Tx_Rnbase_Database_Connection::getInstance()->doSelect(
            'count(*) as nbentries',
            $sTable,
            [
                'enablefieldsoff' => $this->_defaultTrue('/unique/enablefieldsoff/'),
                'where' => implode(' AND ', $where),
            ]
        );

        return !((int) $rs[0]['nbentries'] > 0);
    }

    /**
     * Checks if the submitted value differs from the one in the DB.
     *
     * @param $oRdt
     * @param $value
     */
    public function _differsFromDBValue(&$oRdt, $value)
    {
        $sDeleted = '';

        if (false !== ($sTable = $this->_navConf('/differsfromdb/tablename'))) {
            if (false === ($sField = $this->_navConf('/differsfromdb/field'))) {
                $sField = $oRdt->getName();
            }

            $aDhConf = $this->oForm->_navConf('/control/datahandler/');
            $sKey = $aDhConf['keyname'];
        } else {
            if ($oRdt->hasDataBridge() && ('DB' === $oRdt->oDataBridge->oDataSource->_getType())) {
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

        if (true === $this->_defaultFalse('/differsfromdb/deleted/')) {
            $sDeleted = ' AND deleted != 1';
        }

        $value = addslashes($value);

        $sWhere = $sField.' = \''.$value.'\' AND '.$sKey." = '".$this->oForm->oDataHandler->_currentEntryId()."'".$sDeleted;
        $rows = Tx_Rnbase_Database_Connection::getInstance()->doSelect(
            'count(*) as nbentries',
            $sTable,
            ['where' => $sWhere]
        );

        return !($rows[0]['nbentries'] > 0);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php'];
}

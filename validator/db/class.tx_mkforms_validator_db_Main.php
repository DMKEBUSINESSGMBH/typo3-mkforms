<?php
/**
 * Plugin 'va_std' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_validator_db_Main extends formidable_mainvalidator {
	
	function validate(&$oRdt) {
		
		$sAbsName = $oRdt->getAbsName();
		$mValue = $oRdt->getValue();

		$aKeys = array_keys($this->_navConf('/'));
		reset($aKeys);
		while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {
			
		
			// Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
			if(!$this->canValidate($oRdt, $sKey, $mValue)){
				break;
			}
				
			/***********************************************************************
			*
			*	/unique
			*
			***********************************************************************/

			if($sKey{0} === 'u' && t3lib_div::isFirstPartOfStr($sKey, 'unique')) {
				// field value has to be unique in the database
				// checking this

				if(!$this->_isUnique($oRdt, $mValue)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'DB:unique',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'))
					);

					break;
				}
			}
			/***********************************************************************
			*
			*	/sameasinitial
			*
			***********************************************************************/

			elseif($sKey{0} === 'd' && t3lib_div::isFirstPartOfStr($sKey, 'differsfromdb')) {
				// field value has to differ from the one in the database
				// checking this

				if(!$this->_differsFromDBValue($oRdt, $mValue)) {
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
	
	function _isUnique(&$oRdt, $value) {

		$sDeleted = '';

		if(($sTable = $this->_navConf('/unique/tablename')) !== FALSE) {
			if(($sField = $this->_navConf('/unique/field')) === FALSE) {
				$sField = $oRdt->getName();
			}

			$aDhConf = $this->oForm->_navConf('/control/datahandler/');
			$sKey = $aDhConf['keyname'];

		} else {
			if($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === 'DB')) {
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
		
		if($this->_defaultFalse('/unique/deleted/') === TRUE) {
			$sDeleted = ' AND deleted != 1';
		}

		$value = addslashes($value);

		if($this->oForm->oDataHandler->_edition()) {
			$sWhere = $sField . ' = \'' . $value . '\' AND ' . $sKey . " != '" . $this->oForm->oDataHandler->_currentEntryId() . "'" . $sDeleted;
		} else {
			$sWhere = $sField . " = '" . $value . "'" . $sDeleted;
		}
		
		$sSql = $GLOBALS['TYPO3_DB']->SELECTquery(
			'count(*) as nbentries',
			$sTable,
			$sWhere
		);

		$rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc(
			$this->oForm->_watchOutDB($GLOBALS['TYPO3_DB']->sql_query($sSql),$sSql)
		);
		return !($rs['nbentries'] > 0);

	}
	
	/**
	 * Checks if the submitted value differs from the one in the DB
	 * @param $oRdt
	 * @param $value
	 */
	function _differsFromDBValue(&$oRdt, $value) {

		$sDeleted = '';

		if(($sTable = $this->_navConf('/differsfromdb/tablename')) !== FALSE) {
			if(($sField = $this->_navConf('/differsfromdb/field')) === FALSE) {
				$sField = $oRdt->getName();
			}

			$aDhConf = $this->oForm->_navConf('/control/datahandler/');
			$sKey = $aDhConf['keyname'];

		} else {
			if($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === 'DB')) {
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
		
		if($this->_defaultFalse('/differsfromdb/deleted/') === TRUE) {
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
			$this->oForm->_watchOutDB($GLOBALS['TYPO3_DB']->sql_query($sSql),$sSql)
		);
		return !($rs['nbentries'] > 0);

	}
}


	if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php'])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/validator/db/class.tx_mkforms_validator_db_Main.php']);
	}

?>
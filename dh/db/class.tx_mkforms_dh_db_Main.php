<?php
/**
 * Plugin 'dh_db' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */

tx_rnbase::load('tx_rnbase_util_DB');

class tx_mkforms_dh_db_Main extends formidable_maindatahandler {

	var $__aStoredI18NParent = FALSE;

	public function _doTheMagic($bShouldProcess = TRUE) {

		$tablename = $this->tableName();
		$keyname = $this->keyName();

		if ($this->getForm()->getDataHandler()->_isDraftSubmitted()
			&& !$this->getForm()->_defaultTrue(
				'/control/datahandler/processondraft'
			)
		) {
			$bShouldProcess = FALSE;
		}

		if ($tablename != '' && $keyname != '') {

			if ($this->i18n() && ($aNewI18n = $this->newI18nRequested()) !== FALSE) {

				// first check that parent exists
				if (($aParent = $this->__getDbData($tablename, $keyname, $aNewI18n["i18n_parent"])) === FALSE) {
					$this->oForm->mayday(
						"DATAHANDLER DB cannot create requested i18n for non existing parent:" . $aNewI18n["i18n_parent"]
					);
				}

				//then check that no i18n record exists for requested sys_language_uid on this parent record

				$sSql = $GLOBALS["TYPO3_DB"]->SELECTquery(
					$keyname,
					$tablename,
					"l18n_parent='" . $aNewI18n["i18n_parent"] . "' AND sys_language_uid='" . $aNewI18n["sys_language_uid"] . "'"
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				if ($GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql) !== FALSE) {
					$this->oForm->mayday(
						"DATAHANDLER DB cannot create requested i18n for parent:" . $aNewI18n["i18n_parent"]
						. " with sys_language_uid:" . $aNewI18n["sys_language_uid"] . " ; this version already exists"
					);
				}

				$aChild = array();
				$aChild["sys_language_uid"] = $aNewI18n["sys_language_uid"];
				$aChild["l18n_parent"] = $aNewI18n["i18n_parent"];    // notice difference between i and l
				$aChild["crdate"] = time();
				$aChild["tstamp"] = time();
				$aChild["cruser_id"] = $GLOBALS["TSFE"]->fe_user->user["uid"];
				$aChild["pid"] = $aParent["pid"];

				$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
					$tablename,
					$aChild
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
				$this->bHasCreated = TRUE;
				$this->refreshAllData();
			}

			if ($bShouldProcess && $this->_allIsValid()) {

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

							$aUpdateData = array();

							$this->oForm->_debug(
								"",
								"DB update, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid()
							);

							reset($aFormData);
							while (list($sName,) = each($aFormData)) {
								if (!array_key_exists($sName, $this->oForm->aORenderlets)
									|| !$this->oForm->aORenderlets[$sName]->_translatable()
								) {
									$aUpdateData[$sName] = $aFormData[$sName];
								}
							}

							if (!empty($aUpdateData)) {

								$this->oForm->_debug(
									$aUpdateData,
									"EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "="
									. $editEntry . "] - UPDATING NON TRANSLATED I18N CHILDS"
								);

								$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
									$tablename,
									"l18n_parent = '" . $editEntry . "'",
									$aUpdateData
								);

								$this->oForm->_watchOutDB(
									$GLOBALS["TYPO3_DB"]->sql_query($sSql),
									$sSql
								);
							}
						}

						if ($this->fillStandardTYPO3fields()) {
							if (!array_key_exists("tstamp", $aFormData)) {
								$aFormData['tstamp'] = time();
							}
						}

						$this->oForm->_debug(
							$aFormData,
							"EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "=" . $editEntry
							. "]"
						);

						$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
							$tablename,
							$keyname . " = '" . $editEntry . "'",
							$aFormData
						);

						$this->oForm->_watchOutDB(
							$GLOBALS["TYPO3_DB"]->sql_query($sSql),
							$sSql
						);

						$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

						// updating stored data
						if (!is_array($this->__aStoredData)) {
							$storedData = array();
						} else {
							$storedData = $this->__aStoredData;
						}
						$this->__aStoredData = array_merge($storedData, $aFormData);
						$this->bHasEdited = TRUE;
						$this->_processAfterEdition($this->_getStoredData());
					} else {

						// creating data

						$aFormData = $this->_processBeforeCreation($aFormData);
						if (is_array($aFormData) && count($aFormData) !== 0) {
							if ($this->i18n()) {
								$this->oForm->_debug(
									"",
									"DB insert, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid()
								);
								$aFormData["sys_language_uid"] = $this->i18n_getSysLanguageUid();
							}

							if ($this->fillStandardTYPO3fields()) {
								if (!array_key_exists("pid", $aFormData)) {
									$aFormData['pid'] = $GLOBALS['TSFE']->id;
								}

								if (!array_key_exists("cruser_id", $aFormData)) {
									$aFormData['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
								}

								if (!array_key_exists("crdate", $aFormData)) {
									$aFormData['crdate'] = time();
								}

								if (!array_key_exists("tstamp", $aFormData)) {
									$aFormData['tstamp'] = time();
								}
							}

							$this->oForm->_debug($aFormData, "EXECUTION OF DATAHANDLER DB - INSERTION MODE in " . $tablename);

							$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
								$tablename,
								$aFormData
							);

							$this->oForm->_watchOutDB(
								$GLOBALS["TYPO3_DB"]->sql_query($sSql),
								$sSql
							);

							$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

							$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
							$this->oForm->_debug("", "NEW ENTRY ID [" . $keyname . "=" . $this->newEntryId . "]");

							$this->bHasCreated = TRUE;

							// updating stored data
							$this->__aStoredData = array();
							$this->_getStoredData();
						} else {
							$this->newEntryId = FALSE;
							$this->oForm->_debug("", "NOTHING CREATED IN DB");

							// updating stored data
							$this->__aStoredData = array();
						}

						$this->_processAfterCreation($this->_getStoredData());
					}
				} else {
					$this->oForm->_debug("", "EXECUTION OF DATAHANDLER DB - NOTHING TO DO - SKIPPING PROCESS " . $tablename);
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
	 * Remove all non TCA field from fromData. Can be disabled by cleanNonTcaFields="false"
	 * @param array $aFormData
	 * @return array cleaned data array
	 */
	protected function cleanNonTcaFields($aFormData) {
		if ($this->_defaultTrue("/cleannontcafields")) {
			$tablename = $this->tableName();
			$cols = tx_rnbase_util_TCA::getTcaColumns($tablename);
			if(!empty($cols)) {
				$cols = array_keys($cols);
				if($field = tx_rnbase_util_TCA::getCrdateFieldForTable($tablename))
					$cols[] = $field;
				if($field = tx_rnbase_util_TCA::getTstampFieldForTable($tablename))
					$cols[] = $field;
				if($field = tx_rnbase_util_TCA::getDeletedFieldForTable($tablename))
					$cols[] = $field;
				if($field = tx_rnbase_util_TCA::getLanguageFieldForTable($tablename))
					$cols[] = $field;
				if($field = tx_rnbase_util_TCA::getTransOrigPointerFieldForTable($tablename))
					$cols[] = $field;
				if($field = tx_rnbase_util_TCA::getSortbyFieldForTable($tablename))
					$cols[] = $field;
			}
			if(!empty($cols)) {
				$cols[] = 'pid';
				$aFormData = tx_rnbase_util_Arrays::removeNotIn($aFormData, $cols);
			}
		}

		return $aFormData;
	}

	/**
	 * Look up all data for database from widgets
	 * @return multitype:NULL
	 */
	protected function getDataPreparedForDB() {
		$aRes = array();
		$aKeys = array_keys($this->oForm->aORenderlets);
		reset($aKeys);

		while (list(, $sAbsName) = each($aKeys)) {
			if (!$this->oForm->aORenderlets[$sAbsName]->_renderOnly()
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
	 * called before any database insert or update
	 * @param array $aData
	 */
	protected function _processBeforeInsertion($aData) {

		if (($aUserObj = $this->_navConf("/process/beforeinsertion/")) !== FALSE) {
			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$aData = $this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}

			if (!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);

		return $aData;
	}

	/**
	 * called after any database insert or update
	 * @param array $aData
	 */
	protected function _processAfterInsertion($aData) {
		if (($aUserObj = $this->_navConf("/process/afterinsertion/")) !== FALSE) {
			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	/**
	 * called before insert data into database
	 * @param array $aData
	 * @return array
	 */
	protected function _processBeforeCreation($aData) {

		if (($aUserObj = $this->_navConf("/process/beforecreation/")) !== FALSE) {

			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$aData = $this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}

			if (!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);

		return $aData;
	}

	/**
	 * called before insert data into database
	 * @param array $aData
	 * @return array
	 */
	protected function _processAfterCreation($aData) {
		if (($aUserObj = $this->_navConf("/process/aftercreation/")) !== FALSE) {
			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	protected function _processBeforeEdition($aData) {

		if (($aUserObj = $this->_navConf("/process/beforeedition/")) !== FALSE) {

			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$aData = $this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}

			if (!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);

		return $aData;
	}

	protected function _processAfterEdition($aData) {
		if (($aUserObj = $this->_navConf("/process/afteredition/")) !== FALSE) {
			if ($this->getForm()->getRunnable()->isRunnable($aUserObj)) {
				$this->getForm()->getRunnable()->callRunnable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	function _edition() {

		if ($this->_isClearSubmitted()) {
			// clearsubmitted should display a blank-data page
			// except if edition or new i18n requested

			if ($this->oForm->editionRequested() || $this->newI18nRequested()) {
				return TRUE;
			}

			return FALSE;
		}

		return ($this->_currentEntryId() !== FALSE);
	}

	protected function __getDbData($sTablename, $sKeyname, $iUid, $sFields = "*") {

		$options = array();
		$options['enablefieldsoff'] = 1;
		$options['where'] = $sKeyname . " = " . Tx_Rnbase_Database_Connection::getInstance()->fullQuoteStr($iUid, $sTablename);
		$ret = tx_rnbase_util_DB::doSelect($sFields, $sTablename, $options, 0);

		return count($ret) ? $ret[0] : FALSE;
	}

	function _getStoredData($sName = FALSE) {

		if (empty($this->__aStoredData)) {

			// Hier wird der Record aus der DB geholt
			$this->__aStoredData = array();

			$tablename = $this->tableName();
			$keyname = $this->keyName();

			$editid = $this->_currentEntryId();

			if ($editid !== FALSE) {

				if (($this->__aStoredData = $this->__getDbData($tablename, $keyname, $editid)) !== FALSE) {

					// on va rechercher la configuration du champ en question
					reset($this->oForm->aORenderlets);
					$aRdts = array_keys($this->oForm->aORenderlets);
					while (list(, $fieldName) = each($aRdts)) {
						// Das ist nur für Confirm-Felder interessant
						if (($sConfirm = $this->oForm->aORenderlets[$fieldName]->_navConf("/confirm")) !== FALSE) {
							$this->__aStoredData[$fieldName] = $this->__aStoredData[$sConfirm];
						}
					}
				} else {
					$this->oForm->mayday("DATAHANDLER DB : EDITION OF ENTRY " . $editid . " FAILED");
				}
			}
		}

		if (is_array($this->__aStoredData)) {

			if ($sName !== FALSE) {
				if (array_key_exists($sName, $this->__aStoredData)) {
					return $this->__aStoredData[$sName];
				} else {
					return "";
				}
			}

			reset($this->__aStoredData);

			return $this->__aStoredData;
		}

		return ($sName !== FALSE) ? '' : array();
	}

	function newI18nRequested() {

		if ($this->oForm->aAddPostVars !== FALSE) {
			reset($this->oForm->aAddPostVars);
			while (list($sKey,) = each($this->oForm->aAddPostVars)) {
				if (array_key_exists("action", $this->oForm->aAddPostVars[$sKey])
					&& $this->oForm->aAddPostVars[$sKey]["action"] === "requestNewI18n"
				) {
					if ($this->tablename() === $this->oForm->aAddPostVars[$sKey]["params"]["tablename"] && $this->tablename()) {
						$sOurSafeLock = $this->oForm->_getSafeLock(
							"requestNewI18n" . ":" . $this->oForm->aAddPostVars[$sKey]["params"]["tablename"] . ":"
							. $this->oForm->aAddPostVars[$sKey]["params"]["recorduid"] . ":"
							. $this->oForm->aAddPostVars[$sKey]["params"]["languid"]
						);
						$sTheirSafeLock = $this->oForm->aAddPostVars[$sKey]["params"]["hash"];
						if ($sOurSafeLock === $sTheirSafeLock) {
							return array(
								"i18n_parent" => $this->oForm->aAddPostVars[$sKey]["params"]["recorduid"],
								"sys_language_uid" => $this->oForm->aAddPostVars[$sKey]["params"]["languid"]
							);
						}
					}
				}
			}
		}

		return FALSE;
	}

	function i18n_updateChildsOnSave() {
		return $this->_defaultFalse("/i18n/update_childs_on_save/");
	}

	function i18n_currentRecordUsesDefaultLang() {
		return (intval($this->_getStoredData("sys_language_uid")) === 0);
	}

	function i18n_currentRecordUsesLang() {        // receives unknown number of arguments like 0, -1

		$aParams = func_get_args();

		return in_array(
			intval($this->_getStoredData("sys_language_uid")),
			$aParams
		);
	}

	function i18n_getStoredParent($bStrict = FALSE) {

		if ($this->i18n() && $this->_edition()) {
			if ($this->__aStoredI18NParent === FALSE) {

				$aData = $this->_getStoredData();

				if ($this->i18n_currentRecordUsesDefaultLang()) {

					if ($bStrict === TRUE) {
						return FALSE;
					}

					$this->__aStoredI18NParent = $aData;
				} else {
					$iParent = intval($aData["l18n_parent"]);
					if (($aParent = $this->__getDbData($this->tablename(), $this->keyname(), $iParent)) !== FALSE) {
						$this->__aStoredI18NParent = $aParent;
					}
				}
			}
		}

		return $this->__aStoredI18NParent;
	}

	function i18n_getThisStoredParent($sField, $bStrict = FALSE) {

		$aParent = $this->i18n_getStoredParent($bStrict);
		if (array_key_exists($sField, $aParent)) {
			return $aParent[$sField];
		}

		return FALSE;
	}

	protected function fillStandardTYPO3fields() {
		return $this->_isTrue("/fillstandardtypo3fields");
	}
}

if (defined("TYPO3_MODE")
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_db/api/class.tx_dhdb.php"]
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_db/api/class.tx_dhdb.php"]);
}


<?php

/**
 * Plugin 'ds_db' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_db_Main extends formidable_maindatasource {

	var $oDb = FALSE;

	var $sTable = FALSE;

	var $sKey = FALSE;

	function initDataSet($sKey) {

		if (($this->sTable = $this->_navConf("/table")) === FALSE) {
			$this->oForm->mayday("datasource:DB[name='" . $this->getName() . "'] You have to provide <b>/table</b>.");
		}

		if (($this->sKey = $this->_navConf("/key")) === FALSE) {
			$this->sKey = "uid";
		}

		$sSignature = FALSE;
		$this->initDb();

		$oDataSet = tx_rnbase::makeInstance("formidable_maindataset");

		if ($sKey === "new") {
			// new record to create
			$oDataSet->initFloating($this);
		} else {
			// existing record to grab

			$rSql = $this->oForm->_watchOutDB(
				$this->oDb->exec_SELECTquery(
					"*",
					$this->sTable,
					$this->sKey . "='" . $sKey . "'"
				)
			);

			if (($aDataSet = $this->oDb->sql_fetch_assoc($rSql)) !== FALSE) {
				$oDataSet->initAnchored(
					$this,
					$aDataSet,
					$sKey
				);
			} else {
				if ($this->defaultFalse("/fallbacktonew") === TRUE) {
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

	function &_fetchData($aConfig = array(), $aFilters = array()) {

		$this->initDb();

		$iNumRows = 0;
		$aResults = array();

		$aFilters = $this->beforeSqlFilter($aConfig, $aFilters);

		if (($sSql = $this->_getSql($aConfig, $aFilters)) !== FALSE) {
			$this->oForm->_debug(
				$sSql,
				"DATASOURCE:DB [" . $this->aElement["name"] . "]"
			);

			$sSql = $this->beforeSqlExec($sSql, $aConfig, $aFilters);

			$rSql = $this->oForm->_watchOutDB(
				$this->oDb->sql_query($sSql),
				$sSql
			);

			if ($rSql) {

				$iNumRows = $this->_getTotalNumberOfRows();

				while (($aRs =& $this->oDb->sql_fetch_assoc($rSql)) !== FALSE) {
					$aResults[] = $aRs;
					unset($aRs);
				}
			}
		}

		return array(
			"numrows" => $iNumRows,
			"results" => &$aResults,
		);
	}

	function initDb() {
		if ($this->oDb === FALSE) {
			if (($aLink = $this->_navConf("/link")) !== FALSE) {
				$this->oDb = Tx_Rnbase_Database_Connection::getInstance()->getDatabaseConnection();

				if ($this->oForm->isRunneable(($sHost = $aLink["host"]))) {
					$sHost = $this->callRunneable($sHost);
				}

				if ($this->oForm->isRunneable(($sUser = $aLink["user"]))) {
					$sUser = $this->callRunneable($sUser);
				}

				if ($this->oForm->isRunneable(($sPassword = $aLink["password"]))) {
					$sPassword = $this->callRunneable($sPassword);
				}

				if ($this->oForm->isRunneable(($sDbName = $aLink["dbname"]))) {
					$sDbName = $this->callRunneable($sDbName);
				}

				$this->oDb->sql_pconnect($sHost, $sUser, $sPassword);
				$this->oDb->sql_select_db($sDbName);
			} else {
				$this->oDb =& $GLOBALS["TYPO3_DB"];
			}
		}
	}

	function baseCleanBeforeSession() {
		parent::baseCleanBeforeSession();
		unset($this->oDb);
		$this->oDb = FALSE;
	}

	function beforeSqlExec($sSql, $aConfig, $aFilters) {
		if (($mUserobj = $this->_navConf("/beforesqlexec")) !== FALSE) {
			if ($this->oForm->isRunneable($mUserobj)) {
				$sSql = $this->callRunneable(
					$mUserobj,
					array(
						"sql" => $sSql,
						"config" => $aConfig,
						"filters" => $aFilters
					)
				);
			}
		}

		return $sSql;
	}

	function beforeSqlFilter($aConfig, $aFilters) {
		if (($mUserobj = $this->_navConf("/beforesqlfilter")) !== FALSE) {
			if ($this->oForm->isRunneable($mUserobj)) {
				$aFilters = $this->callRunneable(
					$mUserobj,
					array(
						"config" => $aConfig,
						"filters" => $aFilters
					)
				);
			}
		}

		return $aFilters;
	}

	function _getSql($aConfig = array(), $aFilters = array()) {

		$sSqlBase = "";
		$sSqlFilters = "";
		$sSqlOrderBy = "";
		$sSqlLimit = "";
		$sSqlGroupBy = "";

		if ($this->_isFalse("/sql") && $this->_isFalse("/table")) {
			return FALSE;
		}

		if (($sTable = $this->_navConf("/table")) !== FALSE) {
			$sSqlBase = "SELECT ";
			if (($sFields = $this->_navConf("/fields")) !== FALSE) {
				$sSqlBase .= $sFields . " ";
			} else {
				$sSqlBase .= "* ";
			}
			$sSqlBase .= "FROM " . $sTable . " ";

			if (($aWheres = $this->_navConf("/wheres")) !== FALSE) {
				$sSqlBase .= "WHERE TRUE " . $this->_getAdditionalWheres($aWheres);
			}
		} else {
			$sSqlBase = $this->_navConf("/sql");

			if ($this->oForm->isRunneable($sSqlBase)) {
				$sSqlBase = $this->callRunneable($sSqlBase);
			}
		}

		$sSqlBase = trim($sSqlBase);

		if (($mEnableFields = $this->_defaultFalseMixed("/enablefields")) !== FALSE) {

			if ($mEnableFields === TRUE) {
				// we have to determine the table name

				$oParser = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getSqlParserClass());
				$aParsed = $oParser->parseSQL($sSqlBase);

				if (is_array($aParsed) && count($aParsed["FROM"]) == 1) {
					$sTable = $aParsed["FROM"][0]["table"];
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
			$sEnableFields = "";
		}

		if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr(strtoupper($sSqlBase), "SELECT")) {
			// modify the SQL query to include SQL_CALC_FOUND_ROWS
			$sSqlBase = "SELECT SQL_CALC_FOUND_ROWS " . substr($sSqlBase, strlen("SELECT"));
		} else {
			$this->oForm->mayday(
				"DATASOURCE DB \"" . $this->aElement["name"] . "\" - requires /sql to start with SELECT. Check your XML conf."
			);
		}

		if (strpos(strtoupper($sSqlBase), "WHERE") === FALSE) {
			$sSqlBase .= " WHERE TRUE ";
		}

		if (!empty($aFilters)) {
			$sSqlFilters = " AND (" . implode(" AND ", $aFilters) . ")";
		}

		$sSqlFilters .= $sEnableFields;

		if (($sSqlGroupBy = stristr($sSqlBase, "GROUP BY")) !== FALSE) {
			$sSqlBase = str_replace($sSqlGroupBy, "", $sSqlBase);
		} else {
			$sSqlGroupBy = "";
		}

		if (array_key_exists("sortcolumn", $aConfig) && trim($aConfig["sortcolumn"]) != "") {

			$sSqlOrderBy = " ORDER BY  " . $aConfig["sortcolumn"] . " ";

			if (array_key_exists("sortdirection", $aConfig) && trim($aConfig["sortdirection"]) != "") {
				if (strtoupper($aConfig["sortdirection"]) === "ASC" || strtoupper($aConfig["sortdirection"]) === "DESC") {
					$sSqlOrderBy .= " " . strtoupper($aConfig["sortdirection"]);
				}
			}
		}

		$aLimit = $this->_getRecordWindow(
			$aConfig["page"],
			$aConfig["perpage"]
		);

		$sSqlLimit = $aLimit["sql"];

		return $sSqlBase . " " . $sSqlFilters . " " . $sSqlGroupBy . " " . $sSqlOrderBy . " " . $sSqlLimit;
	}

	function _getTotalNumberOfRows() {
		return $this->oForm->_navConf(
			"/nbrows",
			$this->oDb->sql_fetch_assoc(
				$this->oForm->_watchOutDB(
					$this->oDb->sql_query(
						"SELECT FOUND_ROWS() as nbrows"
					)
				)
			)
		);
	}

	function dset_writeDataSet($sSignature) {

		if (!array_key_exists($sSignature, $this->aODataSets)) {
			return FALSE;
		}

		if ($this->aODataSets[$sSignature]->isFloating()) {

			if ($this->aODataSets[$sSignature]->needsToBeWritten()) {

				if (($mBefore = $this->_navConf("/beforecreation")) !== FALSE) {
					if ($this->oForm->isRunneable($mBefore)) {
						$this->callRunneable(
							$mBefore,
							$this->aODataSets[$sSignature]->getDataSet(),
							$this->aODataSets[$sSignature]
						);
					}
				}

				$aData = $this->aODataSets[$sSignature]->aChangedCells;

				if ($this->defaultTrue("/addsysfields") === TRUE) {
					$aData["crdate"] = time();
					$aData["tstamp"] = time();
				}

				$this->oForm->_watchOutDB(
					$this->oDb->exec_INSERTquery(
						$this->sTable,
						$aData
					)
				);

				$iUid = $this->oDb->sql_insert_id();

				$rSql = $this->oDb->exec_SELECTquery(
					"*",
					$this->sTable,
					$this->sKey . "='" . $iUid . "'"
				);

				if (($aNew = $this->oDb->sql_fetch_assoc($rSql)) !== FALSE) {
					$this->aODataSets[$sSignature]->initAnchored(
						$this,
						$aNew,
						$iUid
					);
				}

				if (($mAfter = $this->_navConf("/aftercreation")) !== FALSE) {
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

				if (($mBefore = $this->_navConf("/beforeedition")) !== FALSE) {
					if ($this->oForm->isRunneable($mBefore)) {
						$this->callRunneable(
							$mBefore,
							$this->aODataSets[$sSignature]->getDataSet(),
							$this->aODataSets[$sSignature]
						);
					}
				}

				$aData = $this->aODataSets[$sSignature]->aChangedCells;
				if ($this->defaultTrue("/addsysfields") === TRUE) {
					$aData["tstamp"] = time();
				}

				$this->oForm->_watchOutDB(
					$this->oDb->exec_UPDATEquery(
						$this->sTable,
						$this->sKey . "='" . $this->aODataSets[$sSignature]->getKey() . "'",
						$aData
					)
				);

				if (($mAfter = $this->_navConf("/afteredition")) !== FALSE) {
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

	function dset_getSignature() {
		die("dsdb:dset_getSignature() disabled");
	}

	function dset_setCellValue($sSignature, $sPath, $mValue, $sAbsName = FALSE) {
		$this->aODataSets[$sSignature]->setCellValue($sPath, $mValue);
	}

	function dset_hasFlexibleStructure() {
		# FALSE as structure may not expand / is not flexible (unlike a flexform)
		return FALSE;
	}

	function _getAdditionalWheres($aWheres, $sPrefix = "") {

		$sTempWhere = "";

		if ($aWheres !== FALSE && is_array($aWheres) && count($aWheres) > 0) {

			$aClauses = array();
			$bClauses = FALSE;

			reset($aWheres);
			while (list($sType, $aWhere) = each($aWheres)) {

				$aTemp = explode("-", $sType);
				$sType = trim(strtoupper($aTemp[0]));
				$bProcess = TRUE;

				if (is_array($aWhere) && array_key_exists("process", $aWhere)) {
					if ($this->oForm->isRunneable($aWhere["process"])) {
						$bProcess = $this->callRunneable(
							$aWhere["process"]
						);
					} else {
						if ($this->oForm->_isFalseVal($aWhere["process"])) {
							$bProcess = FALSE;
						}
					}
				}

				if ($bProcess) {
					switch ($sType) {
						case "WHERE" : {

							if (($mProcess = $this->oForm->_defaultTrue("/process", $aWhere)) !== FALSE) {
								if ($this->oForm->isRunneable($mProcess)) {
									$mProcess = $this->callRunneable($mProcess);
								}
							}

							if ($mProcess === TRUE) {

								if (array_key_exists("value", $aWhere)) {
									$mValue = $aWhere["value"];
								} else {
									$mValue = "";
								}

								if ($this->oForm->isRunneable($mValue)) {
									$mValue = $this->callRunneable($mValue);
								}

								if ($mValue == "") {
									$mValue = "''";
								}

								if ($this->oForm->isRunneable($aWhere["comparison"])) {
									$aWhere["comparison"] = $this->callRunneable($aWhere["comparison"]);
								}

								$sComparison = strtoupper(trim($aWhere["comparison"]));

								if ($bClauses && (($mLogic = $this->oForm->_navConf("/logic", $aWhere)) !== FALSE)) {

									if ($this->oForm->isRunneable($mLogic)) {
										$mLogic = $this->callRunneable($mLogic);
									}

									$aClauses[] = (in_array(trim(strtoupper($mLogic)), array("AND", "OR"))) ? trim(
										strtoupper($mLogic)
									) : "AND";
								}

								$aClauses[] = " " . $sPrefix . $aWhere["term"] . " " . $sComparison . (($sComparison == "IN"
										|| $sComparison == "NOT IN") ? " (" : " '") . $mValue . (($sComparison == "IN"
										|| $sComparison == "NOT IN") ? ") " : "'");
								$bClauses = TRUE;
								break;
							}
						}
						case "BEGINBRACE" : {
							$aClauses[] = "(";
							break;
						}
						case "ENDBRACE" : {
							$aClauses[] = ")";
							break;
						}
						case "LOGIC" : {

							if ($bClauses) {

								if (is_array($aWhere) && array_key_exists("value", $aWhere)) {
									$mValue = $aWhere["value"];
								} else {
									$mValue = $aWhere;
								}

								if ($this->oForm->isRunneable($mValue)) {
									$mValue = $this->callRunneable($mValue);
								}

								$aClauses[] = (in_array(trim(strtoupper($mValue)), array("AND", "OR"))) ? trim(
									strtoupper($mValue)
								) : "";
							}

							break;
						}
					}
				}
			}

			$sTempWhere = implode(" ", $aClauses);
		}

		if ($bClauses && trim($sTempWhere) != "") {
			return " AND (" . $sTempWhere . ")";
		}

		return "";
	}
}

if (defined("TYPO3_MODE")
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/ds/db/class.tx_mkforms_ds_db_Main.php"]
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/ds/db/class.tx_mkforms_ds_db_Main.php"]);
}
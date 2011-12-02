<?php

class formidable_maindatahandler extends formidable_mainobject {

	var $entryId		= null;
	var $forcedId		= null;		// wether $entryId id forced by the PHP, or not
	var $newEntryId		= null;

	var $bDataHandlerOnSubmit = FALSE;	// fills an empty field with data from the datahendler
	
	var $__aStoredData		= array();	// internal use only
	var $__aFormData		= array();	// internal use only
	var $__aFormDataManaged	= array();	// internal use only

	var $__aCols = array();				// columns associated to an existing renderlet
	var $__aListData = array();			// contextual data, containing the current list record
	var $__aParentListData = array();			// contextual data, containing the current list record

	var $aT3Languages = FALSE;

	var $bHasCreated = FALSE;
	var $bHasEdited = FALSE;
	var $aProcessBeforeRenderData = FALSE;

	/**
	 * @param tx_ameosformidable $oForm
	 * @param array $aElement
	 * @param array $aObjectType
	 * @param string $sXPath
	 */
	function _init(&$oForm, $aElement, $aObjectType, $sXPath) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath);
		
		if( !is_null($dhos = $oForm->getConfTS('datahandleronsubmit')) )
				$this->bDataHandlerOnSubmit = $this->isTrueVal($dhos);
		if( ($dhos = $this->_navConf('/datahandleronsubmit')) !== FALSE )
			$this->bDataHandlerOnSubmit = $this->isTrueVal($dhos);
		
		if($this->i18n()) {
			if(($this->i18n_getDefLangUid() === FALSE)) {
				tx_mkforms_util_Div::mayday("DATAHANDLER: <b>/i18n/use</b> is active but no <b>/i18n/defLangUid</b> given");
			}
		}
	}

		/**
		 * Processes data returned by the HTML Form after validation, and only if validated
		 * Note that this is only the 'abstract' definition of this function
		 *  as it must be overloaded in the specialized DataHandlers
		 *
		 * @return	void
		 */
		function _doTheMagic($bShouldProcess = TRUE) {
		}


		/**
		 * Returns the slashstripped GET vars array
		 *
		 * @return	array		GET vars array
		 * @see	formidable_maindatahandler::_GP()
		 */
		function _G() {
			return $this->getForm()->_getRawGet();
		}

		/**
		 * Returns the slashstripped POST vars array
		 *  merged with the _FILES vars array
		 *
		 * @return	array		POST vars array
		 * @see	formidable_maindatahandler::_GP()
		 */
		function _P($sName = FALSE) {
			$aRawPost = $this->getForm()->_getRawPost();
			if($sName !== FALSE) {
				if(array_key_exists($sName, $aRawPost)) {
					return $aRawPost[$sName];
				} else {
					return "";
				}
			}
			
			return $aRawPost;
		}

		/**
		 * Returns the slashstripped _FILES vars array
		 *
		 * @return	array		_FILES vars array
		 * @see	formidable_maindatahandler::_P()
		 */
		function _F() {
			return  $this->getForm()->_getRawFile();
		}
		
		function groupFileInfoByVariable(&$top, $info, $attr) {
			return $this->getForm()->groupFileInfoByVariable($top, $info, $attr);
		}

		/**
		 * Returns the merged GET and POST arrays
		 *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
		 *  and therefore not slashstripped
		 *
		 *	POST overrides GET
		 *
		 * @return	array		GET and POST vars array
		 */
		function _GP() {
			
			return t3lib_div::array_merge_recursive_overrule(
				$this->_G(),
				$this->_P()
			);
		}

		/**
		 * Returns the merged GET and POST arrays
		 *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
		 *  and therefore not slashstripped
		 *
		 *	GET overrides POST
		 *
		 * @return	array		GET and POST vars array
		 */
		function _PG() {
			return t3lib_div::array_merge_recursive_overrule(
				$this->_P(),
				$this->_G()
			);
		}

		/**
		 * Determines if the FORM is submitted
		 *  using the AMEOSFORMIDABLE_SUBMITTED constant for naming the POSTED variable
		 *
		 * @return	boolean
		 */
		
		function _getSubmittedValue($sFormId = FALSE) {
			return $this->getForm()->getSubmittedValue($sFormId);
//			$aP = $this->getForm()->_getRawPost($sFormId);
//			
//			if(array_key_exists("AMEOSFORMIDABLE_SUBMITTED", $aP) && (trim($aP["AMEOSFORMIDABLE_SUBMITTED"]) !== "")) {
//				return trim($aP["AMEOSFORMIDABLE_SUBMITTED"]);
//			}
//
//			return FALSE;
		}
		
		function _isSubmitted($sFormId = FALSE) {

			/*return in_array(
				$this->_getSubmittedValue(),
				array(
					AMEOSFORMIDABLE_EVENT_SUBMIT_FULL,		// full submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH,	// refresh submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_TEST,		// test submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT,		// draft submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR,		// clear submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH,	// clear submit
				)
			);*/
			
			
			return (
				$this->_isFullySubmitted($sFormId) ||
				$this->_isRefreshSubmitted($sFormId) ||
				$this->_isTestSubmitted($sFormId) ||
				$this->_isDraftSubmitted($sFormId) ||
				$this->_isClearSubmitted($sFormId) ||
				$this->_isSearchSubmitted($sFormId)
			);
		}

		function _isFullySubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_FULL);
		}

		function _isRefreshSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH);
		}
		
		function _isTestSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_TEST);
		}

		function _isDraftSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT);
		}

		function _isClearSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR);
		}

		function _isSearchSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH);
		}

		function getSubmitter($sFormId = FALSE) {
			return $this->getForm()->getSubmitter($sFormId);
//			$aP = $this->getForm()->_getRawPost($sFormId);
//			
//			if(array_key_exists("AMEOSFORMIDABLE_SUBMITTER", $aP) && (trim($aP["AMEOSFORMIDABLE_SUBMITTER"]) !== "")) {
//				$sSubmitter = $aP["AMEOSFORMIDABLE_SUBMITTER"];
//				return $sSubmitter;
//			}
//
//			return FALSE;
		}

		function getFormData() {
			reset($this->__aFormData);
			return $this->__aFormData;
		}

		function _getFormData() {
			return $this->getFormData();
		}

		function getThisFormData($sName) {
			$oRdt = $this->getForm()->rdt($sName);
			$sAbsName = $oRdt->getAbsName();
			$sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, "/", $sAbsName);
			return $this->getForm()->navDeepData($sAbsPath, $this->__aFormData);
		}

		function _getThisFormData($sAbsName) {
			return $this->getThisFormData($sAbsName);
		}

		function _processBeforeRender($aData) {
			
			if(($mRunneable = $this->_navConf("/process/beforerender/")) !== FALSE) {
				if($this->getForm()->isRunneable($mRunneable)) {
					$aData = $this->callRunneable(
						$mRunneable,
						$aData
					);
					
					
					if(!is_array($aData)) {
						$aData = array();
					}

					reset($aData);
					return $aData;
				}
			}

			return FALSE;
		}
		
		function getFormDataManaged() {
			$this->getForm()->mayday("getFormDataManaged() is deprecated");
			return $this->_getFormDataManaged();
		}
		
		function _getFormDataManaged() {
			$this->getForm()->mayday("_getFormDataManaged() is deprecated");
			if(empty($this->__aFormDataManaged)) {

				$this->__aFormDataManaged = array();
				$aKeys = array_keys($this->getForm()->aORenderlets);

				reset($aKeys);
				while(list(, $sAbsName) = each($aKeys)) {
					if(!$this->getForm()->getWidget($sAbsName)->_renderOnly() && !$this->getForm()->getWidget($sAbsName)->_readOnly() && $this->getForm()->getWidget($sAbsName)->hasBeenDeeplySubmitted()) {
						$this->__aFormDataManaged[$sAbsName] = $this->getForm()->getWidget($sAbsName)->getValue();
					}
				}
			}

			reset($this->__aFormDataManaged);
			return $this->__aFormDataManaged;
		}

		function _getFlatFormData() {
			$this->getForm()->mayday("_getFlatFormData() is deprecated");
			$aFormData = $this->_getFormData();
			$aRes = array();
			reset($aFormData);
			while(list($sName, $mData) = each($aFormData)) {
				if(array_key_exists($sName, $this->getForm()->aORenderlets)) {
					$aRes[$sName] = $this->getForm()->aORenderlets[$sName]->_flatten($mData);
				}
			}

			reset($aRes);
			return $aRes;
		}
		
		function _getFlatFormDataManaged() {
			$this->getForm()->mayday("_getFlatFormDataManaged() is deprecated");
			$aFormData = $this->_getFormDataManaged();

			$aFlatFormDataManaged = array();
			reset($aFormData);
			while(list($sAbsName, $mData) = each($aFormData)) {
				if(array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {

					if($this->getForm()->useNewDataStructure()) {
						$this->getForm()->mayday("not implemented yet:" . __FILE__ . ":" . __LINE__);
						// data will be stored under abs name
						$sNewName = $sAbsName;
					} else {
						if(!$this->getForm()->getWidget($sAbsName)->_renderOnly() && !$this->getForm()->getWidget($sAbsName)->_readOnly()) {
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
		 * Determines if something was not validated during the validation process
		 * @deprecated use getForm()->getValidationTool()->isAllValid() from form!
		 * @return	boolean	TRUE if everything is valid, FALSE if not
		 */
		function _allIsValid() {
			return $this->getForm()->getValidationTool()->isAllValid();
		}

		function _isValid($sAbsName) {

			if(is_array($this->getForm()->_aValidationErrors) && array_key_exists($sAbsName, $this->getForm()->_aValidationErrors)) {
				$sElementHtmlId = $this->getForm()->getWidget($sAbsName)->_getElementHtmlId();
				if(array_key_exists($sElementHtmlId, $this->getForm()->_aValidationErrorsByHtmlId)) {
					return FALSE;
				}
			}

			return TRUE;
		}
		
		function edition() {
			return $this->_edition();
		}
		
		function creation() {
			return $this->_creation();
		}
		
		/**
		 * Determines if the DataHandler should work in 'edition' mode
		 * Note that this is only the 'abstract' definition of this function
		 *  in the simple case where your DataHandler should never have to edit data
		 *
		 * @return	boolean	TRUE if edition mode, FALSE if not
		 */
		function _edition() {
			return FALSE;
		}

		function _creation() {
			return !$this->_edition();
		}

		/**
		 * Gets the data previously stored by the DataHandler
		 * for edition
		 * Note that this is only the 'abstract' definition of this function
		 *  in the simple case where your DataHandler should never have to edit data
		 *
		 * @return	boolean	TRUE if edition mode, FALSE if not
		 * @see	formidable_maindatahandler::_edition()
		 */
		function _getStoredData($sName = FALSE) {
			
			if($sName !== FALSE) {
				return "";
			}

			return array();
		}

		function getStoredData($sName = FALSE) {
			return $this->_getStoredData($sName);
		}

		function refreshStoredData() {
			$this->__aStoredData = array(); // Ist notwendig, da direkt auf das Array zugegriffen wird!
			$this->__aStoredData = $this->getStoredData();
			// Jetzt initRecord abfahren
			if(($val = $this->getForm()->getConfig()->get('/control/datahandler/initrecord')) !== FALSE) {
				$this->__aStoredData = $this->getForm()->getRunnable()->callRunnable($val, $this->__aStoredData);
			}
		}

		function refreshFormData() {
			$this->__aFormData = array();
			$aKeys = array_keys($this->getForm()->aORenderlets);
			reset($aKeys);
			while(list(, $sAbsName) = each($aKeys)) {
				if(!$this->getForm()->getWidget($sAbsName)->hasParent()) {
					$sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, "/", $sAbsName);
					$this->getForm()->setDeepData(
						$sAbsPath,
						$this->__aFormData,
						$this->getRdtValue($sAbsName)
					);
				}
			}
			
			$this->getForm()->checkPoint(
				array(
					"after-fetching-formdata",
				)
			);
			
			$this->aProcessBeforeRenderData = FALSE;
			
			if(($aNewData = $this->_processBeforeRender($this->__aFormData)) !== FALSE) {
				$aDiff = $this->getForm()->array_diff_recursive($aNewData, $this->__aFormData);
				if(count($aDiff) > 0) {
					$this->aProcessBeforeRenderData = $aDiff;
				}
			}
		}
		
		function alterVirginData($aData) {
			if(($mRun = $this->_navConf("/altervirgindata")) !== FALSE) {
				if($this->getForm()->isRunneable($mRun)) {
					return $this->callRunneable($mRun, $aData);
				}
			}
			
			return $aData;
		}
		
		function alterSubmittedData($aData) {
			if(($mRun = $this->_navConf("/altersubmitteddata")) !== FALSE) {
				if($this->getForm()->isRunneable($mRun)) {
					return $this->callRunneable($mRun, $aData);
				}
			}
			
			return $aData;
		}

		function refreshAllData() {
			$this->refreshStoredData();
			$this->refreshFormData();
		}
		
		function currentId() {
			return $this->_currentEntryId();
		}
		
		function currentEntryId() {
			return $this->_currentEntryId();
		}
		
		function _currentEntryId() {

			if(!is_null($this->newEntryId)) {
				return $this->newEntryId;
			}

			if(!is_null($this->entryId)) {
				return $this->entryId;
			}

			if($this->_isSubmitted() && !$this->_isClearSubmitted()) {
				
				$form_id = $this->getForm()->formid;

				$aPost = t3lib_div::_POST();

				$aPost	= is_array($aPost[$form_id]) ? $aPost[$form_id] : array();
				$aFiles	= is_array($GLOBALS["_FILES"][$form_id]) ? $GLOBALS["_FILES"][$form_id] : array();
				$aP = t3lib_div::array_merge_recursive_overrule($aPost, $aFiles);

				t3lib_div::stripSlashesOnArray($aP);
				
				if(array_key_exists("AMEOSFORMIDABLE_ENTRYID", $aP) && trim($aP["AMEOSFORMIDABLE_ENTRYID"]) !== "") {
					return intval($aP["AMEOSFORMIDABLE_ENTRYID"]);
				}
			}

			return FALSE;
		}
		
		function getHumanFormData() {
			return $this->_getHumanFormData();
		}
		
		function _getHumanFormData() {

			$aFormData = $this->_getFormData();

			$aValues = array();
			$aLabels = array();

			reset($aFormData);
			while(list($elementname, $value) = each($aFormData)) {

				if(array_key_exists($elementname, $this->getForm()->aORenderlets)) {
					$aValues[$elementname] = $this->getForm()->aORenderlets[$elementname]->_getHumanReadableValue($value);
					$aLabels[$elementname] = $this->getForm()->getConfigXML()->getLLLabel($this->getForm()->aORenderlets[$elementname]->aElement["label"]);
				}
			}

			reset($aValues);
			reset($aLabels);

			return array(
				"labels"	=> $aLabels,
				"values"	=> $aValues
			);
		}

		function _initCols() {
			$this->__aCols = array();
		}
		
		function getListData($sKey = FALSE) {
			return $this->_getListData($sKey);
		}

		function isIterating() {
			return $this->__aListData !== FALSE;
		}
		
		function _getListData($sKey = FALSE) {
			if($this->__aListData === FALSE) {
				return FALSE;
			}
			
			$iLastListData = (count($this->__aListData) - 1);
			if($iLastListData < 0) {
				return FALSE;
			}

			if($sKey !== FALSE) {
				
				if(array_key_exists($sKey, $this->__aListData[$iLastListData])) {
					return $this->__aListData[$iLastListData][$sKey];
				} else {
					return FALSE;
				}
			} else {
				if(!empty($this->__aListData)) {
					return $this->__aListData[$iLastListData];
				}
			}

			return array();
		}

		function _getParentListData($sKey = FALSE) {
			if($sKey !== FALSE) {
				
				if(array_key_exists($sKey, $this->__aParentListData)) {

					reset($this->__aParentListData);
					return $this->__aParentListData[$sKey];
				} else {
					
					return FALSE;
				}
			} else {

				reset($this->__aParentListData);
				return $this->__aParentListData;
			}
		}

		function i18n() {
			return $this->_defaultFalse("/i18n/use");
		}

		function i18n_getSysLanguageUid() {
			// http://lists.netfielders.de/pipermail/typo3-at/2005-November/007373.html
			
			if($this->getForm()->rdt("sys_language_uid") !== FALSE) {
				return $this->getForm()->rdt("sys_language_uid")->getValue();
			} else {
				return $GLOBALS["TSFE"]->tmpl->setup["config."]["sys_language_uid"];
			}
		}

		function i18n_getChildRecords($iParentUid) {

			if(($sTableName = $this->tableName())!== FALSE) {

				$aRecords = array();

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"*",
					$sTableName,
					"l18n_parent='" . $iParentUid . "'"
				);

				while(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					$aRecords[$aRs["sys_language_uid"]] = $aRs;
				}

				if(!empty($aRecords)) {
					reset($aRecords);
					return $aRecords;
				}
			}

			return array();
		}

		function i18n_getDefLangUid() {
			return $this->_navConf("/i18n/deflanguid");
		}
		
		function getT3Languages() {
			
			if($this->aT3Languages === FALSE) {

				$this->aT3Languages = array();

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"*",
					"sys_language",
					"1=1" . $this->getForm()->getCObj()->enableFields("sys_language")//"hidden=0"
				);
				
				while(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					$this->aT3Languages[$aRs["uid"]] = $aRs;
				}
			}

			reset($this->aT3Languages);
			return $this->aT3Languages;
		}

		function i18n_currentRecordUsesDefaultLang() {
			return FALSE;
		}

		function tableName() {
			return $this->getForm()->_navConf("/control/datahandler/tablename");
		}

		function keyName() {
			if(($sKey = $this->getForm()->_navConf("/control/datahandler/keyname")) === FALSE) {
				return "uid";
			}
			
			return $sKey;
		}

		function newI18nRequested() {
			return FALSE;
		}

		function i18n_getValueDefaultLang() {
			if($this->i18n()) {

			}
		}

		function i18n_getStoredParent($bStrict = TRUE) {
			return FALSE;
		}
		
		function i18n_getThisStoredParent($sField, $bStrict = TRUE) {
			if(($aStoredParent = $this->i18n_getStoredParent($bStrict)) !== FALSE) {
				if(array_key_exists($sField, $aStoredParent)) {
					return $aStoredParent[$sField];
				}
			}
			
			return FALSE;
		}

		function getRdtValue($sAbsName) {
			if(!array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
				return '';
			}

			if($this->getForm()->getWidget($sAbsName)->bForcedValue === TRUE) {
				return $this->getForm()->getWidget($sAbsName)->mForcedValue;
			}

			if($this->getForm()->getWidget($sAbsName)->i18n_shouldNotTranslate()) {
				if(($aStoredI18NParent = $this->i18n_getStoredParent(TRUE)) !== FALSE) {
					// TODO: do a better mapping between rdt name and the data structure
						// like databridges, see $this->getRdtValue_noSubmit_edit()
						
					$sLocalName = $this->getForm()->getWidget($sAbsName)->getName();
					if(array_key_exists($sLocalName, $aStoredI18NParent)) {
						return $this->getForm()->getWidget($sAbsName)->_unFlatten(
							$aStoredI18NParent[$sLocalName]
						);
					}
				}
			} elseif($this->getForm()->getWidget($sAbsName)->_isClearSubmitted()) {
				
				if($this->getForm()->getWidget($sAbsName)->_edition()) {
					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {
					return $this->getRdtValue_noSubmit_noEdit($sAbsName);
				}

			} elseif($this->getForm()->getWidget($sAbsName)->_isSubmitted()) {

				if($this->getForm()->iForcedEntryId !== FALSE) {
					// we have to use a fresh new record from database
						// so let noSubmit_edit do the job (meaning: don't consider values from submitted POST, but only those from DB)

					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {
					$widget = $this->getForm()->getWidget($sAbsName);
					$mValue = $widget->__getValue();
					if($mValue === FALSE) {
						if($widget->_readOnly()) {
							if($widget->_edition()) {
								return $this->getRdtValue_submit_readonly_edition($sAbsName);
							} else {
								return $this->getRdtValue_submit_readonly_noEdition($sAbsName);
							}
						} else {
							if($widget->_edition()) {
								return $this->getRdtValue_submit_edition($sAbsName);
							} else {
								return $this->getRdtValue_submit_noEdition($sAbsName);
							}
						}
					}
					return $mValue;
				}
			} else {
				if($this->getForm()->getWidget($sAbsName)->_edition()) {
					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {
					return $this->getRdtValue_noSubmit_noEdit($sAbsName);
				}
			}
		}

		function getRdtValue_submit_edition($sAbsName) {
			$widget = $this->getForm()->getWidget($sAbsName);
			$sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
			$sRelPath = $widget->getName();

			// Hier gibt es anscheinend einen Einstiegspunkt, um den aktuellen Wert zu manipulieren
			if($this->aProcessBeforeRenderData !== FALSE && (
					($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
					($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
				)) {
				return $widget->_unFlatten($mValue);
			}

			$aGP = $this->_GP();
			// Das ist für die einfachen Widgets ohne Boxen
			if(array_key_exists($sAbsName, $aGP)) {
				return $aGP[$sAbsName];
			}
			
			if(array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
				// converting abs name to htmlid to introduce lister rowuids in the path
				$sHtmlId = $widget->getElementId();
				
				// removing the formid. prefix
				$sHtmlId = substr($sHtmlId, strlen($this->getForm()->getFormId() . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN));
				
				// converting id to data path
				$sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sHtmlId);
				if(($aRes = $this->getForm()->navDeepData($sAbsPath, $aGP)) !== FALSE) {
					return $aRes;
				} elseif($this->bDataHandlerOnSubmit) {
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

			//get defaultValue if no value is set
			if(($mValue = $this->getForm()->getWidget($sAbsName)->__getDefaultValue()) !== FALSE) {
				return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
			}

			return '';
		}

		function getRdtValue_submit_noEdition($sName) {
			return $this->getRdtValue_submit_edition($sName);
		}

		function getRdtValue_submit_readonly_edition($sName) {			
			# there is a bug here, as renderlet:BOX is readonly
			# and so nothing in a box might be submitted ?!
			if($this->getForm()->aORenderlets[$sName]->hasChilds()) {
				# EDIT: bug might be solved with this hasChilds() test
				return $this->getRdtValue_submit_noEdition($sName);
			} else {
				return $this->getRdtValue_noSubmit_edit($sName);
			}
		}

		function getRdtValue_submit_readonly_noEdition($sName) {
			
			$aGP = $this->_GP();

			$sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sName);

			if(($mValue = $this->getForm()->aORenderlets[$sName]->__getValue()) !== FALSE) {			// value is prioritary if submitted
				return $this->getForm()->aORenderlets[$sName]->_unFlatten($mValue);
			} elseif(($mValue = $this->getForm()->aORenderlets[$sName]->__getDefaultValue()) !== FALSE) {
				return $this->getForm()->aORenderlets[$sName]->_unFlatten($mValue);
			} elseif(($mValue = $this->getForm()->navDeepData($sPath, $aGP)) !== FALSE) {
				
				// if rdt has no childs, do not use the posted data, as it will contain the post-flag "1"
				if($this->getForm()->aORenderlets[$sName]->hasChilds()) {
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
	 * @param $sName
	 * @return string
	 */
	function getRdtValue_noSubmit_noEdit($sName) {

		if(!array_key_exists($sName, $this->getForm()->aORenderlets))
			return '';
		
		$aGP = $this->_isClearSubmitted() ? $this->_G() : $this->_GP();

		$widget = $this->getForm()->getWidget($sName);
		$sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sName);
		$sRelPath = $widget->getName();

		if(($mValue = $widget->__getValue()) !== FALSE) {
			return $widget->_unFlatten($mValue);
		} elseif(
			$this->aProcessBeforeRenderData !== FALSE && (
				($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
				($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
			)
		) {
			return $widget->_unFlatten($mValue);
		} elseif(($mValue = $this->getForm()->navDeepData($sPath, $aGP)) !== FALSE) {
			return $widget->_unFlatten($mValue);
		} elseif(($mValue = $widget->__getDefaultValue()) !== FALSE) {
			return $widget->_unFlatten($mValue);
		}

	}

		function getRdtValue_noSubmit_edit($sAbsName) {
			
			if(array_key_exists($sAbsName, $this->getForm()->aORenderlets)) {
				
				if(($mValue = $this->getForm()->getWidget($sAbsName)->__getValue()) !== FALSE) {	// value a toujours le dessus
					return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
				} else {
					
					$mRes = null;
					
					if($this->getForm()->getWidget($sAbsName)->hasDataBridge()) {

						$oDataSet =& $this->getForm()->getWidget($sAbsName)->dbridged_getCurrentDsetObject();

						// sure that dataset is anchored, as we already tested it to be in noSubmit_edit
						$aData = t3lib_div::array_merge_recursive_overrule(		// allowing GET to set values
							$oDataSet->getData(),
							$this->_G()
						);

						if(($sMappedPath = $this->getForm()->getWidget($sAbsName)->dbridged_mapPath()) !== FALSE) {
							#debug($sMappedPath, $sAbsName . " mapped path");						
							if(($mData = $this->getForm()->navDeepData($sMappedPath, $aData)) !== FALSE) {
								$mRes = $mData;
							}
						} else {
							#debug($sAbsName, "no path mapped!!!");
						}
					} else {
						
						$sPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
						$sRelPath = $this->getForm()->getWidget($sAbsName)->getName();
						
						if(
							$this->aProcessBeforeRenderData !== FALSE && (
								($mValue = $this->getForm()->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
								($mValue = $this->getForm()->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
							)
						) {
							return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
						}

						$sNewName = $this->getForm()->getWidget($sAbsName)->getName();
						
						$aStored = $this->_getStoredData();
						
						if (is_array($aStored)) {
							$aData = t3lib_div::array_merge_recursive_overrule(		// allowing GET to set values
								$aStored,
								$this->_G()
							);
							
							if(array_key_exists($sNewName, $aData)) {
								$mRes = $this->getForm()->getWidget($sAbsName)->_unFlatten($aData[$sNewName]);
							}
						}
					}
				//@TODO War auskommentiert, warum!?
					if(is_null($mRes) || $this->getForm()->getWidget($sAbsName)->_emptyFormValue($mRes)) {
						if(($mValue = $this->getForm()->getWidget($sAbsName)->__getDefaultValue()) !== FALSE) {
							return $this->getForm()->getWidget($sAbsName)->_unFlatten($mValue);
						}
					}
					
					return $mRes;
				}
			}

			return '';
		}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.maindatahandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.maindatahandler.php']);
}
?>
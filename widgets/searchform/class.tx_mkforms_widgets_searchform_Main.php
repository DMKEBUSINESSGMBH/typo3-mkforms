<?php
/**
 * Plugin 'rdt_searchform' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_searchform_Main extends formidable_mainrenderlet {

	var $oDataSource = FALSE;
	var $aCriterias = FALSE;
	var $aFilters = FALSE;
	var $aDescendants = FALSE;

	function _init(&$oForm, $aElement, $aObjectType, $sXPath) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath);
		$this->_initDescendants();	// early init (meaning before removing unprocessed rdts)
	}

	function _render() {

		$this->_initData();

		$aChildBags = $this->renderChildsBag();
		$sCompiledChilds = $this->renderChildsCompiled($aChildBags);

		if($this->isRemoteReceiver() && !$this->mayDisplayRemoteReceiver()) {
			return array(
				"__compiled" => "",
			);
		}

		return array(
			"__compiled" => $this->_displayLabel($sLabel) . $sCompiledChilds,
			"childs" => $aChildBags
		);
	}

	function getDescendants() {

		$aDescendants = array();
		$sMyName = $this->getAbsName();

		$aRdts = array_keys($this->oForm->aORenderlets);
		reset($aRdts);
		while(list(, $sName) = each($aRdts)) {
			if($this->oForm->aORenderlets[$sName]->isDescendantOf($sMyName)) {
				$aDescendants[] = $sName;
			}
		}

		return $aDescendants;
	}

	function _initDescendants($bForce = FALSE) {
		if($bForce === TRUE || $this->aDescendants === FALSE) {
			$this->aDescendants = $this->getDescendants();
		}
	}

	function _initData() {
		$this->_initDescendants(TRUE);	// done in _init(), re-done here to filter out unprocessed rdts
		$this->_initCriterias();	// if submitted, take from post ; if not, take from session
									// and inject values into renderlets
		$this->_initFilters();
		$this->_initDataSource();
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function isRemoteSender() {
	    return ($this->_navConf("/remote/mode") === "sender");
	}

	function isRemoteReceiver() {
	    return ($this->_navConf("/remote/mode") === "receiver");
	}

	function _initDataSource() {

    	if($this->isRemoteSender()) {
	    	return;
    	}

		if($this->oDataSource === FALSE) {

			if(($sDsToUse = $this->_navConf("/datasource/use")) === FALSE) {
				$this->oForm->mayday("RENDERLET SEARCHFORM - requires /datasource/use to be properly set. Check your XML conf.");
			} elseif(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
				$this->oForm->mayday("RENDERLET SEARCHFORM - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
			}

			$this->oDataSource =& $this->oForm->aODataSources[$sDsToUse];
		}
	}

	function clearFilters() {

		reset($this->aDescendants);
		while(list(, $sName) = each($this->aDescendants)) {
			$this->oForm->aORenderlets[$sName]->setValue("");
		}

		$this->aCriterias = FALSE;
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		$aAppData["rdt_lister"][$this->oForm->formid][$this->getAbsName()]["criterias"] = array();

		if($this->isRemoteReceiver()) {
			$aAppData["rdt_lister"][$this->getRemoteSenderFormId()][$this->getRemoteSenderAbsName()]["criterias"] = array();
		}
	}

	function getCriterias() {
		return $this->aCriterias;
	}


	function getRemoteSenderFormId() {
		if($this->isRemoteReceiver()) {
			if(($sSenderFormId = $this->_navConf("/remote/senderformid")) !== FALSE) {
				return $sSenderFormId;
			}
		}

		return FALSE;
	}

	function getRemoteSenderAbsName() {
		if($this->isRemoteReceiver()) {
			if(($sSenderAbsName = $this->_navConf("/remote/senderabsname")) !== FALSE) {
				return $sSenderAbsName;
			}
		}

		return FALSE;
	}

	function _initCriterias() {

		if($this->aCriterias === FALSE) {

			$bUpdate = FALSE;

		    if($this->isRemoteReceiver()) {

		        if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
		        }

		        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
		        }
		    } else {
		        $sFormId = $this->oForm->formid;
		        $sSearchAbsName = $this->getAbsName();
		    }

			$this->aCriterias = array();

			$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];

			if(!array_key_exists("rdt_lister", $aAppData)) {
				$aAppData["rdt_lister"] = array();
			}

			if(!array_key_exists($sFormId, $aAppData["rdt_lister"])) {
				$aAppData["rdt_lister"][$sFormId] = array();
			}

			if(!array_key_exists($sSearchAbsName, $aAppData["rdt_lister"][$sFormId])) {
				$aAppData["rdt_lister"][$sFormId][$sSearchAbsName] = array();
			}

			if(!array_key_exists("criterias", $aAppData["rdt_lister"][$sFormId][$sSearchAbsName])) {
				$aAppData["rdt_lister"][$sFormId][$sSearchAbsName]["criterias"] = array();
			}

			if($this->shouldUpdateCriteriasClassical()) {

				$bUpdate = TRUE;

				if($this->isRemoteReceiver()) {
					// set in session
					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {
						$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
						$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;
						$this->aCriterias[$sRemoteAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
					}
				} else {
					// set in session
					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {
						if(!$this->oForm->aORenderlets[$sAbsName]->hasChilds()) {
							$this->aCriterias[$sAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
						}
					}
				}
			} elseif($this->shouldUpdateCriteriasRemoteReceiver()) {

				$bUpdate = TRUE;
				if($this->isRemoteReceiver()) {
					// set in session

					$aRawPost = $this->oForm->_getRawPost($sFormId);

					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {

						$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
						$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;
						$sRemoteAbsPath = str_replace(".", "/", $sRemoteAbsName);

						$mValue = $this->oForm->navDeepData($sRemoteAbsPath, $aRawPost);
						$this->aCriterias[$sRemoteAbsName] = $mValue;
						$this->oForm->aORenderlets[$sAbsName]->setValue($mValue);	// setting value in receiver

					}
				}
			}

			if($bUpdate === TRUE) {

				if($this->_getParamsFromGET()) {

					$aGet = (t3lib_div::_GET($sFormId)) ? t3lib_div::_GET($sFormId) : array();

					reset($aGet);
					while(list($sAbsName, ) = each($aGet)) {
						if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {

							$this->aCriterias[$sAbsName] = $aGet[$sAbsName];

							$this->oForm->aORenderlets[$sAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);

							$aTemp = array(
								$sFormId => array(
									$sAbsName => 1,
								),
							);

							$this->oForm->setParamsToRemove($aTemp);
						}
					}
				}

				$aAppData["rdt_lister"][$sFormId][$sSearchAbsName]["criterias"] = $this->aCriterias;
			} else {
				// take from session
				$this->aCriterias = $aAppData["rdt_lister"][$sFormId][$sSearchAbsName]["criterias"];

				if($this->isRemoteReceiver()) {

					if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
			            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
			        }

			        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
			            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
			        }

					reset($this->aCriterias);
					while(list($sAbsName, ) = each($this->aCriterias)) {
						$sRelName = $this->oForm->relativizeName(
							$sAbsName,
							$sSearchAbsName
						);

						$sLocalAbsName = $this->getAbsName() . "." . $sRelName;
						if(array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
							$this->oForm->aORenderlets[$sLocalAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);
						}
					}
				} else {
					reset($this->aCriterias);
					while(list($sAbsName, ) = each($this->aCriterias)) {
						if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
							$this->oForm->aORenderlets[$sAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);
						}
					}
				}
			}
		}
	}

	function shouldUpdateCriteriasRemoteReceiver() {

		if($this->isRemoteReceiver()) {
			if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
	            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
	        }

	        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
	            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
	        }

			if($this->oForm->oDataHandler->_isSearchSubmitted($sFormId) || $this->oForm->oDataHandler->_isFullySubmitted($sFormId)) {	# full submit to allow no-js browser to search
				reset($this->aDescendants);
				while(list(, $sAbsName) = each($this->aDescendants)) {
					$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
					$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;

					if($this->oForm->aORenderlets[$sAbsName]->hasSubmitted($sFormId, $sRemoteAbsName)) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	function shouldUpdateCriteriasClassical() {
		if($this->oForm->oDataHandler->_isSubmitted() === TRUE) {
			reset($this->aDescendants);
			while(list(, $sAbsName) = each($this->aDescendants)) {
				if(
					array_key_exists($sAbsName, $this->oForm->aORenderlets) &&
					$this->oForm->aORenderlets[$sAbsName]->hasSubmitted() &&
					$this->oForm->oDataHandler->_isSearchSubmitted()) {	// the mode is not determined by the renderlet anymore, but rather by the datahandler (one common submit per page, anyway)

					return TRUE;
				}
			}
		} else {
			if($this->_getParamsFromGET()) {
				$aGet = (t3lib_div::_GET($this->oForm->formid)) ? t3lib_div::_GET($this->oForm->formid) : array();
				$aIntersect = array_intersect(array_keys($aGet), array_keys($this->oForm->aORenderlets));
				return count($aIntersect) > 0;	// are there get params in url matching at least one criteria in the searchform ?
			}
		}

		return FALSE;
	}

	function shouldUpdateCriterias() {

		$bRes = FALSE;

		if($this->isRemoteReceiver()) {
			if(($bRes = $this->shouldUpdateCriteriasRemoteReceiver()) === FALSE && $this->mayDisplayRemoteReceiver()) {
				$bRes = $this->shouldUpdateCriteriasClassical();
			}
		} else {
			return $this->shouldUpdateCriteriasClassical();
		}

		return FALSE;
	}

	function mayDisplayRemoteReceiver() {
		return $this->isRemoteReceiver() && !$this->_defaultTrue("/remote/invisible");
	}

	function processBeforeSearch($aCriterias) {

		if(($aBeforeSearch = $this->_navConf("/beforesearch")) !== FALSE && $this->oForm->isRunneable($aBeforeSearch)) {
			$aCriterias = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeSearch, $aCriterias);
		}

		if(!is_array($aCriterias)) {
			$aCriterias = array();
		}

		return $aCriterias;
	}

	function processAfterSearch($aResults) {

		if(($aAfterSearch = $this->_navConf("/aftersearch")) !== FALSE && $this->oForm->isRunneable($aAfterSearch)) {
			$aResults = $this->getForm()->getRunnable()->callRunnableWidget($this, $aAfterSearch, $aResults);
		}

		if(!is_array($aResults)) {
			$aResults = array();
		}

		return $aResults;
	}

	function _initFilters() {

		if($this->aFilters === FALSE) {

			$this->aFilters = array();

			$aCriterias = $this->processBeforeSearch($this->aCriterias);
			reset($aCriterias);

			if ($this->isRemoteReceiver()) {

				if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
		        }

		        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
		        }

				while(list($sRdtName,) = each($aCriterias)) {

					$sRelName = $this->oForm->relativizeName(
						$sRdtName,
						$sSearchAbsName
					);


					$sLocalAbsName = $this->getAbsName() . "." . $sRelName;

					if(array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
						$oRdt =& $this->oForm->aORenderlets[$sLocalAbsName];

						if($oRdt->_searchable()) {

							$sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

							if(!$oRdt->_emptyFormValue($sValue)) {
								$this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
							}
						}
					}
				}
			} else {
				while(list($sRdtName,) = each($aCriterias)) {
					if(array_key_exists($sRdtName, $this->oForm->aORenderlets)) {
						$oRdt =& $this->oForm->aORenderlets[$sRdtName];

						if($oRdt->_searchable()) {

							$sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

							if(!$oRdt->_emptyFormValue($sValue)) {
								$this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
							}
						}
					}
				}
			}

			reset($this->aFilters);
		}
	}

	function &_getFilters() {
		$this->_initFilters();
		reset($this->aFilters);
		return $this->aFilters;
	}

	function &fetchData($aConfig = array()) {
		return $this->_fetchData($aConfig);
	}

	function &_fetchData($aConfig = array()) {

		return $this->processAfterSearch(
			$this->oDataSource->_fetchData(
				$aConfig,
				$this->_getFilters()
			)
		);
	}

	function _renderOnly() {
		return $this->_defaultTrue("/renderonly");
	}

	function _getParamsFromGET() {
		return $this->_defaultFalse("/paramsfromget");
	}

	function _searchable() {
		return FALSE;
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_searchform/api/class.tx_rdtsearchform.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_searchform/api/class.tx_rdtsearchform.php"]);
	}
?>
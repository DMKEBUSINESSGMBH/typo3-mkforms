<?php
/**
 * Plugin 'rdt_i18n' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_i18n_Main extends formidable_mainrenderlet {

	var $aOButtons = array();

	function _render() {

		if(!$this->oForm->oDataHandler->i18n()) {
			$this->oForm->mayday("renderlet:I18N <b>'" . $this->_getName() . "'</b>: Datahandler has to declare <b>/i18n/use=true</b> for renderlet:I18N to work");
		}

		$aHtmlBag = array();

		$aCurData = $this->oForm->oDataHandler->_getListData();

		if(empty($aCurData)) {
			$aCurData = $this->oForm->oDataHandler->i18n_getStoredParent();
		}

		if(!empty($aCurData)) {

			$aLangs = $this->oForm->oDataHandler->getT3Languages();

			$iUid = $aCurData["uid"];
			$aChildRecords = $this->oForm->oDataHandler->i18n_getChildRecords($iUid);
			$aChildLanguages = array_keys($aChildRecords);

			reset($aLangs);
			$aKeys = array_keys($aLangs);
			while(list(, $iLangUid) = each($aKeys)) {

				if($iLangUid != $this->oForm->oDataHandler->i18n_getDefLangUid()) {

					if(tx_mkforms_util_Div::getEnvExecMode() !== "BE" || $GLOBALS["BE_USER"]->checkLanguageAccess($iLangUid)) {
						$aLang = $aLangs[$iLangUid];

						if(in_array($iLangUid, $aChildLanguages)) {
							$bExists = TRUE;
							$sEvent = <<<EVENT

							\$aParams = \$this->getUserObjParams();

							return \$this->majixRequestEdition(
								\$aParams["childrecords"][\$aParams["sys_language_uid"]]["uid"],
								\$this->oDataHandler->tablename()
							);
EVENT;
						} else {
							$bExists = FALSE;
							$sEvent = <<<EVENT

							\$aParams = \$this->getUserObjParams();

							return \$this->majixRequestNewI18n(
								\$this->oDataHandler->tablename(),
								\$aParams["record"]["uid"],
								\$aParams["sys_language_uid"]
							);
EVENT;
						}

						$aConf = array(
							"type" => "BUTTON",
							"label" => $aLang["title"] . ($bExists ? "" : " [NEW]"),
							"onclick-default" => array(
								"runat" => "client",
								"userobj" => array(
									"php" => $sEvent,
								)
							)
						);

						if(($aCustomConf = $this->_navConf("/stdbutton")) !== FALSE) {
							$aConf = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
								$aConf,
								$aCustomConf
							);
						}

						$sName = $this->_getName() . "-record-" . $iUid . "-lang-" . $iLangUid;
						$aConf["name"] = $sName;

						$this->aOButtons[$sName] = $this->oForm->_makeRenderlet(
							$aConf,
							$this->sXPath . $sName. "/",
							FALSE,
							$this,
							FALSE,
							FALSE
						);

						$this->oForm->aORenderlets[$sName] =& $this->aOButtons[$sName];


						$iIndex = $this->oForm->getRunnable()->pushForcedUserObjParam(
							array(
								"translation_exists" => $bExists,
								"sys_language_uid" => $iLangUid,
								"childrecords" => $aChildRecords,
								"childlanguages" => $aChildLanguages,
								"record" => $aCurData,
								"lang" => $aLang,
								"langs" => $aLangs,
							)
						);

						$aRendered = $this->aOButtons[$sName]->render();
						$aHtmlBag[] = $this->aOButtons[$sName]->wrap($aRendered["__compiled"]);

						$this->oForm->getRunnable()->pullForcedUserObjParam($iIndex);
					}
				}
			}
		}

		return implode("", $aHtmlBag);
	}

	function _getFlag($sPath, $bExists, $aLang) {

		if(($aFlags = $this->_navConf("/flags")) !== FALSE) {

			$aDefinition = FALSE;

			reset($aFlags);
			while(list(, $aFlag) = each($aFlags)) {
				if($aFlag["uid"] == $aLang["uid"]) {
					$aDefinition = $aFlag;
					break;
				}
			}

			if($aDefinition !== FALSE) {

				if($bExists === TRUE) {
					$aDefinition = $aDefinition["exists"];
				} else {
					$aDefinition = $aDefinition["dontexist"];
				}

				if(array_key_exists("path", $aDefinition)) {
					// on renvoie l'image

					if($this->oForm->isRunneable($aDefinition["path"])) {
						$aDefinition["path"] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aDefinition["path"]);
					}

					return array(
						"type" => "image",
						"value" => $this->oForm->toWebPath($aDefinition["path"])
					);
				} elseif(array_key_exists("label", $aDefinition)) {
					// on renvoie le label

					if($this->oForm->isRunneable($aDefinition["label"])) {
						$aDefinition["label"] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aDefinition["label"]);
					}

					return array(
						"type" => "text",
						"value" => $this->oForm->getConfigXML()->getLLLabel($aDefinition["label"])
					);
				} else {
					/* on renvoie le flag par defaut */
				}
			} else {
				/* on renvoie le flag par defaut */
			}
		}

		if($bExists === TRUE) {

			$sTypoScript =<<<TYPOSCRIPT

	file = GIFBUILDER
	file {
		XY = [10.w], [10.h]

		10 = IMAGE
		10.file = {$sPath}
	}

TYPOSCRIPT;

		} else {

			$sTypoScript =<<<TYPOSCRIPT

	file = GIFBUILDER
	file {
		XY = [10.w], [10.h]

		10 = IMAGE
		10.file = {$sPath}

		15 = EFFECT
		15.value = gamma=4
	}

TYPOSCRIPT;

		}

		$this->getForm()->getRunnable()->callRunnableWidget($this,
			array(
				"userobj" => array(
					"ts" => $sTypoScript
				)
			)
		);

		return array(
			"type" => "image",
			"value" => $this->oForm->toWebPath(
				$this->getForm()->getCObj()->IMG_RESOURCE(
					$this->oForm->aLastTs
				)
			)
		);
	}

	function _listable() {
		return $this->oForm->_defaultTrue("/listable/", $this->aElement) && $this->oForm->oDataHandler->i18n();
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->oForm->_defaultTrue("/activelistable/", $this->aElement);
	}

	function _renderOnly($bForAjax = FALSE) {
		return TRUE;
	}

	function cleanBeforeSession() {
		$this->aOButtons = array();
		$this->baseCleanBeforeSession();
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_i18n/api/class.tx_rdti18n.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_i18n/api/class.tx_rdti18n.php"]);
	}

<?php
/**
 * Plugin 'rdr_template' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */
tx_rnbase::load('tx_rnbase_util_Templates');

class tx_mkforms_renderer_template_Main extends formidable_mainrenderer {

	var $aCustomTags	= array();
	var $aExcludeTags	= array();
	var $sTemplateHtml	= FALSE;

	function getTemplatePath() {

		$sPath = $this->_navConf("/template/path/");
		if($this->getForm()->isRunneable($sPath)) {
			$sPath = $this->callRunneable(
				$sPath
			);
		}

		if(is_string($sPath)) {
			return tx_mkforms_util_Div::toServerPath($sPath);
		}

		return "";
	}

	function getTemplateSubpart() {
		 $sSubpart = $this->_navConf("/template/subpart/");
		 if($this->getForm()->isRunneable($sSubpart)) {
			$sSubpart = $this->callRunneable(
				$sSubpart
			);
		}

		return $sSubpart;
	}

	function getTemplateHtml() {

		if($this->sTemplateHtml === FALSE) {
			$sPath = $this->getTemplatePath();

			if(!empty($sPath)) {
				if(!file_exists($sPath)) {
					$this->getForm()->mayday("RENDERER TEMPLATE - Template file does not exist <b>" . $sPath . "</b>");
				}

				if(($sSubpart = $this->getTemplateSubpart()) !== FALSE) {
					$mHtml = tx_rnbase_util_Templates::getSubpart(
						Tx_Rnbase_Utility_T3General::getUrl($sPath),
						$sSubpart
					);

					if(trim($mHtml) == "") {
						$this->getForm()->mayday("RENDERER TEMPLATE - The given template <b>'" . $sPath . "'</b> with subpart marker " . $sSubpart . " <b>returned an empty string</b> - Check your template");
					}
				} else {
					$mHtml = Tx_Rnbase_Utility_T3General::getUrl($sPath);
					if(trim($mHtml) == "") {
						$this->getForm()->mayday("RENDERER TEMPLATE - The given template <b>'" . $sPath . "'</b> with no subpart marker <b>returned an empty string</b> - Check your template");
					}
				}

			} elseif(($mHtml = $this->_navConf("/html")) !== FALSE) {

				if(is_array($mHtml)) {
					if($this->getForm()->isRunneable($mHtml)) {
						$mHtml = $this->callRunneable($mHtml);
					} else {
						$mHtml = $mHtml["__value"];
					}
				}

				if(trim($mHtml) == "") {
					$this->getForm()->mayday("RENDERER TEMPLATE - The given <b>/html</b> provides an empty string</b> - Check your template");
				}
			} else {
				$this->getForm()->mayday("RENDERER TEMPLATE - You have to provide either <b>/template/path</b> or <b>/html</b>");
			}

			$this->sTemplateHtml = $mHtml;
		}

		return $this->sTemplateHtml;
	}

	function _render($aRendered) {

		$aRendered = $this->beforeDisplay($aRendered);

		$this->getForm()->_debug($aRendered, "RENDERER TEMPLATE - rendered elements array");

		if(($sErrorTag = $this->_navConf("/template/errortag/")) === FALSE) {
			if(($sErrorTag = $this->_navConf("/html/errortag")) === FALSE) {
				$sErrorTag = "errors";
			}
		}

		if($this->getForm()->isRunneable($sErrorTag)) {
			$sErrorTag = $this->callRunneable(
				$sErrorTag
			);
		}

		$aErrors = array();
		$aCompiledErrors = array();
		$aErrorKeys = array_keys($this->getForm()->_aValidationErrors);
		while(list(, $sRdtName) = each($aErrorKeys)) {
			$sShortRdtName = $this->getForm()->aORenderlets[$sRdtName]->_getNameWithoutPrefix();
			if(trim($this->getForm()->_aValidationErrors[$sRdtName]) !== "") {

				$sWrapped = $this->wrapErrorMessage($this->getForm()->_aValidationErrors[$sRdtName]);
				$aErrors[$sShortRdtName] = $this->getForm()->_aValidationErrors[$sRdtName];
				$aErrors[$sShortRdtName . "."]["tag"] = $sWrapped;
				$aCompiledErrors[] = $sWrapped;
			}
		}

		if(strtolower(trim($this->_navConf("/template/errortagcompilednobr"))) == "true") {
			$aErrors["__compiled"] = implode("", $aCompiledErrors);
		} else {
			$aErrors["__compiled"] = implode("<br />", $aCompiledErrors);
		}

		$aErrors["__compiled"] = $this->compileErrorMessages($aCompiledErrors);

		$aErrors["cssdisplay"] = ($this->getForm()->oDataHandler->_allIsValid()) ? "none" : "block";

		$aRendered = $this->displayOnlyIfJs($aRendered);
		$aRendered[$sErrorTag] = $aErrors;

		$mHtml = $this->getTemplateHtml();
		$sForm = $this->getForm()->getTemplateTool()->parseTemplateCode(
			$mHtml,
			$aRendered,
			$this->aExcludeTags,
			$this->_defaultTrue("/template/clearmarkersnotused")
		);

		return $this->_wrapIntoForm($sForm);
	}

	function beforeDisplay($aRendered) {

		if(($aUserObj = $this->_navConf("/beforedisplay/")) !== FALSE) {

			if($this->getForm()->isRunneable($aUserObj)) {
				$aRendered = $this->callRunneable(
					$aUserObj,
					$aRendered
				);
			}
		}

		if(!is_array($aRendered)) {
			$aRendered = array();
		}

		reset($aRendered);
		return $aRendered;
	}

	function cleanBeforeSession() {
		$this->sTemplateHtml = FALSE;
		$this->baseCleanBeforeSession();
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_template/api/class.tx_rdrtemplate.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_template/api/class.tx_rdrtemplate.php"]);
	}


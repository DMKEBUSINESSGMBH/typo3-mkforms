<?php
/**
 * Plugin 'rdr_std' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_renderer_std_Main extends formidable_mainrenderer {

	function _render($aRendered) {

		$aRendered = $this->displayOnlyIfJs($aRendered);

		$this->oForm->_debug($aRendered, "RENDERER STANDARD - rendered elements array");
		$sForm = $this->_collate($aRendered);

		if(!$this->oForm->oDataHandler->_allIsValid()) {
			$errDiv = $this->getForm()->getFormId(). AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN.'errors';
			$sValidationErrors = '<div id="'.$errDiv.'" class="errors"><div class="error">' . implode('</div><div class="error">', $this->oForm->_aValidationErrorsByHtmlId) . '</div></div><hr class="separator" />';
		}

		$this->oForm->additionalHeaderData(
			"<link href=\"" . $this->sExtWebPath . "res/css/style.css\" type=\"text/css\" rel=\"stylesheet\" />",
			"formidable-rdrstd-style.css"
		);

		return $this->_wrapIntoForm($sValidationErrors . $sForm);
	}

	function _collate($aHtml) {

		$sHtml = "";

		if(is_array($aHtml) && count($aHtml) > 0) {
			reset($aHtml);

			while(list($sName, $aChannels) = each($aHtml)) {
				if(array_key_exists($sName, $this->oForm->aORenderlets)) {
					if($this->getForm()->getWidget($sName)->defaultWrap()) {
						$sHtml .= "\n<div class='".$this->getForm()->sDefaultWrapClass."-rdtwrap'>" . str_replace("{PARENTPATH}", $this->oForm->_getParentExtSitePath(), $aChannels["__compiled"]) . "</div>\n";
					} else {
						$sHtml .= "\n" . str_replace("{PARENTPATH}", $this->oForm->_getParentExtSitePath(), $aChannels["__compiled"]) . "\n";
					}
				}
			}
		}

		return $sHtml;
	}
}

<?php
/** 
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_tab_Main extends formidable_mainrenderlet {
	

	function _render() {

		if(($sContentAbsName = $this->_navConf("/content")) === FALSE) {
			$this->oForm->mayday("renderlet:TAB <b>" . $this->getAbsName() . "</b> - requires <b>/content</b> to be set to the absolute name of a renderlet.");
		}
		
		if(!array_key_exists($sContentAbsName, $this->oForm->aORenderlets)) {
			$this->oForm->mayday("renderlet:TAB <b>" . $this->getAbsName() . "</b> - <b>/content</b> points to the renderlet absolutely named '<b>" . $sContentAbsName . "</b>', that does not exist within the application.");
		}

		$sBegin = "<li id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . ">";
		$sBegin .= "<a class=\"rdttab\" href=\"" . $GLOBALS["TSFE"]->anchorPrefix . "#" . $this->oForm->aORenderlets[$sContentAbsName]->_getElementHtmlId() . "\">";

		$sEnd = "</a>";
		$sEnd .= "</li>";

		if($this->hasChilds()) {
			$aChilds = $this->renderChildsBag();
			$sCompiledChilds = $this->renderChildsCompiled(
				$aChilds
			);
		} else {
			$sCompiledChilds = $this->getLabel();
		}

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sBegin . $sCompiledChilds . $sEnd,
			"childs" => $aChilds
		);

		return $aHtmlBag;
	}

	function _readOnly() {
		return TRUE;
	}

	function _renderOnly() {
		return TRUE;
	}
	
	function _renderReadOnly() {
		return $this->_render();
	}
	
	function _debugable() {
		return $this->oForm->_defaultFalse("/debugable/", $this->aElement);
	}

	function majixReplaceData($aData) {
		return $this->buildMajixExecuter(
			"replaceData",
			$aData
		);
	}

	function majixToggleDisplay() {
		return $this->buildMajixExecuter(
			"toggleDisplay"
		);
	}
	
	function mayHaveChilds() {
		return TRUE;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tab/api/class.tx_rdttab.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tab/api/class.tx_rdttab.php"]);
	}

?>
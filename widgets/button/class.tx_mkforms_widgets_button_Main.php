<?php
/**
 * Plugin 'rdt_button' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_button_Main extends formidable_mainrenderlet {

	var $sMajixClass = "Button";
	var $aLibs = array(
		"rdt_button_class" => "res/js/button.js",
	);


	function _render() {
		return $this->_renderReadOnly();
	}

	function _renderReadOnly() {
		$sValue = $this->getValue();
		$sLabel = $this->getLabel();
		$sInput = "<input type=\"button\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . htmlspecialchars($sLabel) . "\" " . $this->_getAddInputParams() . " />";

		return array(
			"__compiled" => $sInput,
			"input" => $sInput,
			"label" => $sLabel,
			"value" => $sValue,
		);
	}

	function _renderOnly() {
		return TRUE;
	}

	function _readOnly() {
		return TRUE;
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->_defaultTrue("/activelistable/");
	}
}

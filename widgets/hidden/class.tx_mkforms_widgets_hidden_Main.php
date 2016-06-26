<?php
/**
 * Plugin 'rdt_hidden' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_hidden_Main extends formidable_mainrenderlet {

	function _render() {

		$sValue = $this->getValue();
		$sValueForHtml = $this->getValueForHtml($sValue);

		$sInput = "<input type=\"hidden\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sValueForHtml . "\"" . $this->_getAddInputParams() . " />";

		return array(
			"__compiled" => $sInput,
			"input" => $sInput,
			"value" => $sValue,
		);
	}

	function _renderReadonly() {
		return $this->_render();
	}

	function _activeListable() {
		return TRUE;
	}
}

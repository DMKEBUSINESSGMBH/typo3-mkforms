<?php
/**
 * Plugin 'rdt_passthru' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_passthru_Main extends formidable_mainrenderlet {

	function _render() {
		return "";
	}

	function _sqlSearchClause($value, $fieldprefix = "") {
		return $fieldprefix . $this->_navConf("/name") . " = '" . $value . "'";
	}

	function _listable() {
		return FALSE;
	}

	function maySubmit() {
		return FALSE;
	}
}

<?php

class formidable_mainscriptingmethods {

	function _init(&$oForm) {
		$this->oForm =& $oForm;
	}

	function process($sMethod, $mData, $sArgs) {

		$aParams = $this->oForm->getTemplateTool()->parseTemplateMethodArgs($sArgs);
		$sMethodName = strtolower('method_' . $sMethod);

		if (method_exists($this, $sMethodName)) {
			return $this->$sMethodName($mData, $aParams);
		} else {
			if (is_object($mData) && is_string($sMethod) && method_exists($mData, $sMethod)) {
				return $mData->{$sMethod}($aParams, $this->oForm);
			}
		}

		return AMEOSFORMIDABLE_LEXER_FAILED;
	}
} // END class formidable_mainscriptingmethods

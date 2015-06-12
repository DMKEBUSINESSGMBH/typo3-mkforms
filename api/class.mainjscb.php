<?php

class formidable_mainjscb {

	var $aConf = array();
	var $oForm = null;

	function init(&$oForm, $aConf) {
		$this->oForm = $oForm;
		$this->aConf = $aConf;
	}

	function majixExec($sMethod, $oForm) {

		$aArgs = func_get_args();
		$sMethod = array_shift($aArgs);
		array_shift($aArgs);
		return $oForm->buildMajixExecuter(
			"executeCbMethod",
			array(
				"cb" => $this->aConf,
				"method" => $sMethod,
				"params" => $aArgs
			),
			$oForm->getFormId()
		);
	}
}

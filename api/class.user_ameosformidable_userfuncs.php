<?php

class user_ameosformidable_userfuncs {
	function getAdditionalHeaderData() {
		
		$aRes = array();
		if(isset($GLOBALS["tx_ameosformidable"]) && isset($GLOBALS["tx_ameosformidable"]["headerinjection"])) {
			
			reset($GLOBALS["tx_ameosformidable"]["headerinjection"]);
			while(list(, $aHeaderSet) = each($GLOBALS["tx_ameosformidable"]["headerinjection"])) {
				$aRes[] = implode("\n", $aHeaderSet["headers"]);
			}
		}

		reset($aRes);
		return implode("", $aRes);
	}
}

if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.user_ameosformidable_userfuncs.php"])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.user_ameosformidable_userfuncs.php"]);
}

?>
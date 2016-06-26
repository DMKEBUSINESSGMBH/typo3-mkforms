<?php

class user_ameosformidable_userfuncs {
	function getAdditionalHeaderData() {

		$aRes = array();
		if (isset($GLOBALS["tx_ameosformidable"]) && isset($GLOBALS["tx_ameosformidable"]["headerinjection"])) {

			reset($GLOBALS["tx_ameosformidable"]["headerinjection"]);
			while (list(, $aHeaderSet) = each($GLOBALS["tx_ameosformidable"]["headerinjection"])) {
				$aRes[] = implode("\n", $aHeaderSet["headers"]);
			}
		}

		reset($aRes);

		return implode("", $aRes);
	}
}

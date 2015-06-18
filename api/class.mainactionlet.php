<?php

class formidable_mainactionlet extends formidable_mainobject {

	function _doTheMagic() {
	}
}

if (defined("TYPO3_MODE")
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainactionlet.php"]
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainactionlet.php"]);
}
?>
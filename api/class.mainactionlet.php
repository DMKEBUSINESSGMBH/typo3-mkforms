<?php

	class formidable_mainactionlet extends formidable_mainobject {
		
		function _doTheMagic() {
		}
	}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainactionlet.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainactionlet.php"]);
	}
?>
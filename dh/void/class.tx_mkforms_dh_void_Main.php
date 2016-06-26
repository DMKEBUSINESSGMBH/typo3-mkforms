<?php

/**
 * Plugin 'dh_void' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_dh_void_Main extends formidable_maindatahandler {

	function _doTheMagic($bShouldProcess = TRUE) {

		if ($bShouldProcess && $this->_allIsValid()) {
			$this->oForm->_debug("void do nothing with data", "DATAHANDLER VOID - EXECUTION");
		}
	}
}

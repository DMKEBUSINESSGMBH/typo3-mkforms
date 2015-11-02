<?php

/**
 * Plugin 'act_userobj' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_action_userobj_Main extends formidable_mainactionlet {

	function _doTheMagic($aRendered, $sForm) {
		if ($this->oForm->oDataHandler->_allIsValid()) {
			$this->callRunneable($this->aElement);
		}
	}
}

if (defined('TYPO3_MODE')
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/action/userobj/api/class.tx_mkforms_action_userobj_Main.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/action/userobj/class.tx_mkforms_action_userobj_Main.php']);
}


<?php
/**
 * Plugin 'rdr_void' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_renderer_void_Main extends formidable_mainrenderer {

	function _render($aRendered) {

		$this->oForm->_debug($aRendered, "RENDERER VOID - rendered elements array which are not displayed");
		return $this->_wrapIntoForm("");
	}
}

<?php
/**
 * Plugin 'rdt_lbl' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_label_Main extends formidable_mainrenderlet {

	function _renderReadOnly() {

		$aItems = $this->_getItems();
		$value = $this->oForm->oDataHandler->getThisFormData($this->_getName());

		$sCaption = $value;

		if(count($aItems) > 0) {

			reset($aItems);
			while(list($itemindex, $aItem) = each($aItems))
			{
				if($aItem["value"] == $value) {
					$sCaption = $aItem["caption"];
				}
			}
		}

		$sCaption = htmlspecialchars($this->oForm->getConfigXML()->getLLLabel($sCaption));

		return $sCaption;
	}

	function _renderOnly() {
		return TRUE;
	}

	function _readOnly() {
		return TRUE;
	}
}

<?php
/**
 * Plugin 'rdt_radio' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_radio_Main extends formidable_mainrenderlet {

	var $sMajixClass = 'Radio';
	var $aLibs = array(
		'rdt_radio_class' => 'res/js/radio.js',
	);

	var $sDefaultLabelClass = 'label-radio';
	var $bCustomIncludeScript = TRUE;

	function _render() {

		$aHtmlBag = array();
		$sCurValue = $this->getValue();
		$sRadioGroup = '';

		// on construit la liste des �l�ments du groupe radio

		$optionsList = '';
		$aItems = $this->_getItems();
		$aSubRdts = array();

		$aHtmlBag['value'] = $sCurValue;

		if(count($aItems) > 0) {

			$aHtml = array();

			reset($aItems);
			while(list($itemindex, $aItem) = each($aItems)) {

				// item configuration
				$aConfig = array_merge($this->aElement, $aItem);

				$selected = '';
				$isSelected = false;
				if($aItem['value'] == $sCurValue) {
					$isSelected = true;
					$selected = ' checked="checked" ';
				}

				$sCaption = isset($aItem['caption'])
						? $this->getForm()->getConfig()->getLLLabel($aItem['caption'])
						: $aItem['value']
					;

				$sId = $this->_getElementHtmlId() . '_' . $itemindex;
				$aSubRdts[] = $sId;
				$this->sCustomElementId = $sId;
				$this->includeScripts();

				$sValue = $aItem['value'];

				$sInput = '<input type="radio" name="' . $this->_getElementHtmlName() . '" id="' . $sId . '" value="' . $aItem['value'] . '" ' . $selected . $this->_getAddInputParams($aItem) . ' />';

				$aConfig['sId'] = $sId;

				// nur Label ohne Tag ausgeben
				if($this->_navConf('/addnolabeltag') !== FALSE){
					$sLabelStart = $sLabelEnd = '';
				}
				else {
					$token = self::getToken();
					$sLabelTag = $this->getLabelTag($token, $aConfig);
					$sLabelTag = explode($token, $sLabelTag);
					$sLabelStart = $sLabelTag[0];
					$sLabelEnd = '</label>';
				}
				$sLabelTag = $sLabelStart.$sCaption.$sLabelEnd;

				$aHtmlBag[$sValue . '.'] = array(
					'label' => $sCaption,
					'label.' => array(
						'tag' => $sLabelTag,
						'tag.' => array(
							'start' => $sLabelStart,
							'end' => $sLabelEnd,
						),
					),
					'input' => $sInput,
					'value' => $sValue,
					'caption' => $sCaption,
					'selected' => $isSelected,
				);

				$aHtml[] = (($selected !== '') ? $this->_wrapSelected($sInput . $sLabelTag) : $this->_wrapItem($sInput . $sLabelTag));
				$this->sCustomElementId = FALSE;
			}

			reset($aHtml);
			$sRadioGroup = $this->_implodeElements($aHtml);
		}

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				'name' => $this->_getElementHtmlName(),
				'radiobuttons' => $aSubRdts,
				'bParentObj' => TRUE,
			)
		);

		$sInput = $this->_implodeElements($aHtml);
		$aHtmlBag['input'] = $sInput;
		$aHtmlBag['__compiled'] = $this->_displayLabel( $this->getLabel() ) . $sRadioGroup;

		reset($aHtmlBag);
		return $aHtmlBag;
	}

	function _getHumanReadableValue($data) {

		$aItems = $this->_getItems();

		reset($aItems);
		while(list(, $aItem) = each($aItems)) {

			if($aItem['value'] == $data) {
				return $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);
			}
		}

		return $data;
	}




	function _getSeparator() {

		if(($mSep = $this->_navConf('/separator')) === FALSE) {
			$mSep = "\n";
		} else {
			if($this->oForm->isRunneable($mSep)) {
				$mSep = $this->getForm()->getRunnable()->callRunnableWidget($this, $mSep);
			}
		}

		return $mSep;
	}

	function _implodeElements($aHtml) {

		if(!is_array($aHtml)) {
			$aHtml = array();
		}

		return implode(
			$this->_getSeparator(),
			$aHtml
		);
	}

	function _wrapSelected($sHtml) {

		if(($mWrap = $this->_navConf('/wrapselected')) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace('|', $sHtml, $mWrap);

		} else {
			$sHtml = $this->_wrapItem($sHtml);
		}

		return $sHtml;
	}

	function _wrapItem($sHtml) {

		if(($mWrap = $this->_navConf('/wrapitem')) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace('|', $sHtml, $mWrap);
		}

		return $sHtml;
	}

	function _displayLabel($sLabel) {

		// für bestehende projekte, das main label darf nicht die klasse -radio haben!
		$sDefaultLabelClass = $this->sDefaultLabelClass;
		$this->sDefaultLabelClass = $this->getForm()->sDefaultWrapClass.'-label';

		$aConfig =  $this->aElement;
		// via default, kein for tag!
		if(!isset($aConfig['labelfor'])) $aConfig['labelfor'] = 0;

		$sLabel = $this->getLabelTag($sLabel, $aConfig);

		// label zurücksetzen
		$this->sDefaultLabelClass = 'label-radio';

		return $sLabel;
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->_defaultTrue('/activelistable/');
	}
}


	if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_radio/api/class.tx_rdtradio.php'])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_radio/api/class.tx_rdtradio.php']);
	}

?>
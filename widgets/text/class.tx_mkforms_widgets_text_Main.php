<?php


class tx_mkforms_widgets_text_Main extends formidable_mainrenderlet {

	function _render() {

		$sValue = $this->getValue();
		$sLabel = $this->getLabel();
		$inputType = $this->getInputType();

		$aAdditionalParams = implode(' ', (array) $this->getAdditionalParams());
		$sInput = '<input type="' . $inputType . '" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" value="' . $this->getValueForHtml($sValue) . '"' . $this->_getAddInputParams($aAdditionalParams) . ' '.$aAdditionalParams.' />';

		return array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
			"label" => $sLabel,
			"value" => $sValue,
		);
	}

	protected function getAdditionalParams(){
		$aAdditionalParams = array();
		if(($sMaxLength = $this->_navConf('/maxlength')) !== FALSE) {
			$aAdditionalParams[] = 'maxlength="'.$sMaxLength.'"';
		}
		return $aAdditionalParams;
	}

	function getValue() {
		$sValue = parent::getValue();
		if($this->defaultFalse("/convertfromrte/")){
			$aParseFunc["parseFunc."] = $GLOBALS["TSFE"]->tmpl->setup["lib."]["parseFunc_RTE."];
			$sValue = $this->getForm()->getCObj()->stdWrap($sValue, $aParseFunc);
		}
		return $sValue;
	}

	function mayHtmlAutocomplete() {
		return TRUE;
	}

	/**
	 * @return string
	 */
	protected function getInputType(){
		if(!($type = $this->_navConf('/inputtype'))) {
			$type = 'text';
		}
		return $type;
	}
}

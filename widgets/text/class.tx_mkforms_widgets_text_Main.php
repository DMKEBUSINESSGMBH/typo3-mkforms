<?php


class tx_mkforms_widgets_text_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $sValue = $this->getValue();
        $sLabel = $this->getLabel();
        $inputType = $this->getInputType();

        $aAdditionalParams = implode(' ', (array) $this->getAdditionalParams());
        $sInput = '<input type="' . $inputType . '" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" value="' . $this->getValueForHtml($sValue) . '"' . $this->_getAddInputParams($aAdditionalParams) . ' '.$aAdditionalParams.' />';

        return array(
            '__compiled' => $this->_displayLabel($sLabel) . $sInput,
            'input' => $sInput,
            'label' => $sLabel,
            'value' => $sValue,
        );
    }

    protected function getAdditionalParams()
    {
        $aAdditionalParams = array();
        if (($sMaxLength = $this->_navConf('/maxlength')) !== false) {
            $aAdditionalParams[] = 'maxlength="'.$sMaxLength.'"';
        }

        return $aAdditionalParams;
    }

    public function getValue()
    {
        $sValue = parent::getValue();
        if ($this->defaultFalse('/convertfromrte/')) {
            $aParseFunc['parseFunc.'] = $GLOBALS['TSFE']->tmpl->setup['lib.']['parseFunc_RTE.'];
            $sValue = $this->getForm()->getCObj()->stdWrap($sValue, $aParseFunc);
        }

        return $sValue;
    }

    public function mayHtmlAutocomplete()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function getInputType()
    {
        if (!($type = $this->_navConf('/inputtype'))) {
            $type = 'text';
        }

        return $type;
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_text/api/class.tx_mkforms_widgets_text_Text.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_text/api/class.tx_mkforms_widgets_text_Text.php']);
}

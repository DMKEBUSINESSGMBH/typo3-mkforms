<?php
/**
 * Plugin 'rdt_pwd' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_pwd_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $sLabel = $this->getLabel();

        $sInput = '<input type="password" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.$this->getValueForHtml().'"'.$this->_getAddInputParams().' />';

        $aHtmlBag = array(
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'input' => $sInput,
        );

        return $aHtmlBag;
    }

    public function mayHtmlAutocomplete()
    {
        return true;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_pwd/api/class.tx_rdtpwd.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_pwd/api/class.tx_rdtpwd.php'];
}

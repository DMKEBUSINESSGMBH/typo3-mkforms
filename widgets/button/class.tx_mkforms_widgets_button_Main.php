<?php
/**
 * Plugin 'rdt_button' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_button_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Button';
    public $aLibs = array(
        'rdt_button_class' => 'res/js/button.js',
    );


    public function _render()
    {
        return $this->_renderReadOnly();
    }

    public function _renderReadOnly()
    {
        $sValue = $this->getValue();
        $sLabel = $this->getLabel();
        $sInput = '<input type="button" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" value="' . htmlspecialchars($sLabel) . '" ' . $this->_getAddInputParams() . ' />';

        return array(
            '__compiled' => $sInput,
            'input' => $sInput,
            'label' => $sLabel,
            'value' => $sValue,
        );
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function _readOnly()
    {
        return true;
    }

    public function _activeListable()
    {
        // listable as an active HTML FORM field or not in the lister
        return $this->_defaultTrue('/activelistable/');
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_button/api/class.tx_rdtbutton.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_button/api/class.tx_rdtbutton.php']);
}

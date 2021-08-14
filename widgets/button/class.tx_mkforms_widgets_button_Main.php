<?php
/**
 * Plugin 'rdt_button' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_button_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Button';
    public $aLibs = [
        'rdt_button_class' => 'res/js/button.js',
    ];

    public function _render()
    {
        return $this->_renderReadOnly();
    }

    public function _renderReadOnly()
    {
        $sValue = $this->getValue();
        $sLabel = $this->getLabel();
        $sInput = '<input type="button" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.htmlspecialchars($sLabel).'" '.$this->_getAddInputParams().' />';

        return [
            '__compiled' => $sInput,
            'input' => $sInput,
            'label' => $sLabel,
            'value' => $sValue,
        ];
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

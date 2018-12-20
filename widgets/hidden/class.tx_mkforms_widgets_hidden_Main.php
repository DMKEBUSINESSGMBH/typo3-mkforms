<?php
/**
 * Plugin 'rdt_hidden' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_hidden_Main extends formidable_mainrenderlet
{

    /**
     * {@inheritDoc}
     * @see formidable_mainrenderlet::_render()
     */
    public function _render()
    {
        $value = $this->getValue();

        $inputHtml = '<input ' .
            'type="hidden" ' .
            'name="' . $this->_getElementHtmlName() . '" ' .
            'id="' . $this->_getElementHtmlId() . '" ' .
            'value="' . $this->getValueForHtml($value) . '"' .
            $this->_getAddInputParams() .
        ' />';

        return [
            '__compiled' => $inputHtml,
            'input' => $inputHtml,
            'value' => $value,
        ];
    }

    /**
     * {@inheritDoc}
     * @see formidable_mainrenderlet::_renderReadonly()
     */
    public function _renderReadonly()
    {
        return $this->_render();
    }

    /**
     * {@inheritDoc}
     * @see formidable_mainrenderlet::_activeListable()
     */
    public function _activeListable()
    {
        return true;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_hidden/api/class.tx_rdthidden.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_hidden/api/class.tx_rdthidden.php']);
}

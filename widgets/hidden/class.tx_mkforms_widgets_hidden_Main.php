<?php
/**
 * Plugin 'rdt_hidden' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_hidden_Main extends formidable_mainrenderlet
{
    /**
     * {@inheritdoc}
     *
     * @see formidable_mainrenderlet::_render()
     */
    public function _render()
    {
        $value = $this->getValue();

        $inputHtml = '<input '.
            'type="hidden" '.
            'name="'.$this->_getElementHtmlName().'" '.
            'id="'.$this->_getElementHtmlId().'" '.
            'value="'.$this->getValueForHtml($value).'"'.
            $this->_getAddInputParams().
        ' />';

        return [
            '__compiled' => $inputHtml,
            'input' => $inputHtml,
            'value' => $value,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see formidable_mainrenderlet::_renderReadonly()
     */
    public function _renderReadonly()
    {
        return $this->_render();
    }

    /**
     * {@inheritdoc}
     *
     * @see formidable_mainrenderlet::_activeListable()
     */
    public function _activeListable()
    {
        return true;
    }
}

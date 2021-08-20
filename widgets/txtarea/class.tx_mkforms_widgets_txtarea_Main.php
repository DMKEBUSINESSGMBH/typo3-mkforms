<?php
/**
 * Plugin 'rdt_txtarea' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_txtarea_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $sValue = $this->getValue();
        $sLabel = $this->getLabel();
        $sValue = $this->oForm->getConfigXML()->getLLLabel($sValue);

        $sAddInputParams = $this->_getAddInputParams();

        /* adaptation for XHTML1.1 strict validation */

        if (false === strpos($sAddInputParams, 'rows')) {
            $sAddInputParams = ' rows="2" '.$sAddInputParams;
        }

        if (false === strpos($sAddInputParams, 'cols')) {
            $sAddInputParams = ' cols="20" '.$sAddInputParams;
        }

        // sollen in der textarea durch das jQuery autoresize Plugin
        // die evtl. anfallenden scroll balken entfernt werden
        // es gibt nur eine Unterstützung für jQuery!!!
        if ('jquery' == $this->getForm()->getJSLoader()->getJSFrameworkId() && $this->defaultFalse('/autoresize')) {
            $this->sMajixClass = 'TxtArea';
            $this->bCustomIncludeScript = true;
            $this->aLibs['rdt_autoresize_class'] = 'res/js/autoresize.min.js';
            $this->aLibs['rdt_txtarea_class'] = 'res/js/txtarea.js';
            //damit im JS bekannt ist, ob autoresize gesetzt ist
            $this->includeScripts(['autoresize' => $this->defaultFalse('/autoresize')]);
        }

        $sValueForHtml = $this->getValueForHtml($sValue);
        $sInput = '<textarea name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'"'.$sAddInputParams.'>'.$sValueForHtml.'</textarea>';

        return [
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'input' => $sInput,
            'label' => $sLabel,
            'value' => $sValue,
        ];
    }

    public function _getHumanReadableValue($sValue)
    {
        return nl2br(htmlspecialchars($sValue));
    }
}

<?php
/**
 * Plugin 'rdt_submit' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_submit_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $sLabel = $this->getLabel();
        $sValue = $sLabel ? ' value="'.$sLabel.'"' : '';
        if (false !== ($sPath = $this->_navConf('/path'))) {
            $sPath = tx_mkforms_util_Div::toWebPath(
                $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath)
            );
            $sHtml = '<input type="image" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'"'.$sValue.' src="'.$sPath.'"'.$this->_getAddInputParams().' />';
        } else {
            $sHtml = '<input type="submit" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'"'.$sValue.' '.$this->_getAddInputParams().' />';
        }

        return $sHtml;
    }

    public function getSubmitMode()
    {
        $sMode = $this->_navConf('/mode');
        if ($this->oForm->isRunneable($sMode)) {
            $sMode = $this->getForm()->getRunnable()->callRunnableWidget($this, $sMode);
        }

        if (is_string($sMode)) {
            return strtolower(trim($sMode));
        }

        return 'full';
    }

    public function _getEventsArray()
    {
        $aEvents = parent::_getEventsArray();

        if (!array_key_exists('onclick', $aEvents)) {
            $aEvents['onclick'] = array();
        }

        $aEvents['onclick'][] = 'MKWrapper.stopEvent(event)';

        $sMode = $this->getSubmitMode();

        $aAddPost = array(
            $this->_getElementHtmlNameWithoutFormId() => '1',        // to simulate default browser behaviour
        );

        if ('refresh' == $sMode || false !== $this->_navConf('/refresh')) {
            $sOnclick = $this->oForm->oRenderer->_getRefreshSubmitEvent();
        } elseif ('draft' == $sMode || false !== $this->_navConf('/draft')) {
            $sOnclick = $this->oForm->oRenderer->_getDraftSubmitEvent();
        } elseif ('test' == $sMode || false !== $this->_navConf('/test')) {
            $sOnclick = $this->oForm->oRenderer->_getTestSubmitEvent();
        } elseif ('clear' == $sMode || false !== $this->_navConf('/clear')) {
            $sOnclick = $this->oForm->oRenderer->_getClearSubmitEvent();
        } elseif ('search' == $sMode || false !== $this->_navConf('/search')) {
            $sOnclick = $this->oForm->oRenderer->_getSearchSubmitEvent();
        } else {
            $sOnclick = $this->oForm->oRenderer->_getFullSubmitEvent();
        }

        $sAddPostVars = "Formidable.f('".$this->oForm->formid."').addFormData(".$this->oForm->array2json($aAddPost).');';
        $sAddPostVars .= $sOnclick;

        // prüfe Confirm und füge if hinzu
        if (false !== ($sConfirm = $this->_navConf('/confirm'))) {
            $sAddPostVars = 'if(confirm(\''.$sConfirm.'\')){'.$sAddPostVars.'}';
        }

        // Nach dem JavaScript-Submit schickt der IE nochmal den richtigen Submit des Submit-Buttons ab
        // was dazu führt das das Formular 2x abgeschickt wird. ein return false sollte das problem beheben.
        $sAddPostVars .= ' return false;';

        $aEvents['onclick'][] = $sAddPostVars;

        reset($aEvents);

        return $aEvents;
    }

    public function _hasThrown($sEvent, $sWhen = false)
    {
        if ('click' === $sEvent) {
            // handling special click server event on rdt_submit
            // special because has to work without javascript
            return $this->hasSubmitted();
        }

        return parent::_hasThrown($sEvent, $sWhen);
    }

    public function _searchable()
    {
        return $this->_defaultFalse('/searchable/');
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function isNaturalSubmitter()
    {
        return true;
    }

    public function _activeListable()
    {
        return $this->_defaultTrue('/activelistable');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/submit/class.tx_mkforms_widgets_submit_Main.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/submit/class.tx_mkforms_widgets_submit_Main.php'];
}

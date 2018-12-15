<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_modalbox_Main extends formidable_mainrenderlet
{
    public $aLibs = array(
        'rdt_modalbox_class' => 'res/js/modalbox.js',
    );

    public $bCustomIncludeScript = true;
    public $sMajixClass = 'ModalBox';

    public function _render()
    {

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            array(
                'followScrollVertical' => $this->defaultTrue('/followscrollvertical'),
                'followScrollHorizontal' => $this->_defaultTrue('/followscrollhorizontal'),
            )
        );

        return '';
    }

    public function _renderReadOnly()
    {
        return $this->_render();
    }
    public function _readOnly()
    {
        return true;
    }
    public function _renderOnly($bForAjax = false)
    {
        return true;
    }
    public function mayHaveChilds()
    {
        return true;
    }

    public function majixShowFreshBox($aConfig = array(), $aTags = array())
    {
        $this->initChilds(
            true    // existing renderlets in $this->oForm->aORenderlets will be overwritten
        );    // re-init childs before rendering

        $this->oForm->oDataHandler->refreshAllData();

        return $this->majixShowBox($aConfig, $aTags);
    }

    /**
     * @param array $aConfig
     * @param array $aTags
     *
     * @return array
     */
    public function majixShowBox($aConfig = array(), $aTags = array())
    {
        if (tx_mkforms_util_Div::getEnvExecMode() !== 'EID') {
            $aEventsBefore = array_keys($this->oForm->aRdtEvents);
        }

        $aChildsBag = $this->renderChildsBag();
        $aChildsBag = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aChildsBag, $aTags);

        if (tx_mkforms_util_Div::getEnvExecMode() !== 'EID') {
            $aEventsAfter = array_keys($this->oForm->aRdtEvents);
            $aAddedKeys = array_diff($aEventsAfter, $aEventsBefore);
            $aAddedEvents = array();
            foreach ($aAddedKeys as $sKey) {
                $aAddedEvents[$sKey] = $this->oForm->aRdtEvents[$sKey];
                unset($this->oForm->aRdtEvents[$sKey]);
                // unset because if rendered in a lister,
                    // we need to be able to detect the new events even if they were already declared by other loops in the lister
            }

            $aConfig['attachevents'] = $aAddedEvents;
        }

        $sCompiledChilds = $this->renderChildsCompiled(
            $aChildsBag
        );

        $aConfig['html'] = $sCompiledChilds;

        return $this->buildMajixExecuter(
            'showBox',
            $aConfig
        );
    }

    /**
     * @return array
     */
    public function majixCloseBox()
    {
        return $this->buildMajixExecuter(
            'closeBox'
        );
    }

    public function loadModalBox(&$oForm)
    {
        $oJsLoader = $this->getForm()->getJSLoader();
        $oJsLoader->loadScriptaculous();

        $sPath = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . tx_rnbase_util_Extensions::siteRelPath('ameos_formidable') . 'api/base/rdt_modalbox/res/js/modalbox.js';

        $oForm->additionalHeaderData(
            '<script type="text/javascript" src="' . $oJsLoader->getScriptPath($sPath) . '"></script>',
            'rdt_modalbox_class'
        );
    }

    // this has to be static !!!
    public static function loaded(&$aParams)
    {
        $aParams['form']->getJSLoader()->loadScriptaculous();
    }

    public function majixRepaint()
    {
        return $this->buildMajixExecuter(
            'repaint',
            $this->renderChildsCompiled(
                $this->renderChildsBag()
            )
        );
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_modalbox/api/class.tx_rdtmodalbox.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_modalbox/api/class.tx_rdtmodalbox.php']);
}

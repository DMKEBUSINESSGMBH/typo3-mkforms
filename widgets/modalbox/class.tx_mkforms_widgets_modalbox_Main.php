<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_modalbox_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_modalbox_class' => 'res/js/modalbox.js',
    ];

    public $bCustomIncludeScript = true;
    public $sMajixClass = 'ModalBox';

    public function _render()
    {
        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'followScrollVertical' => $this->defaultTrue('/followscrollvertical'),
                'followScrollHorizontal' => $this->_defaultTrue('/followscrollhorizontal'),
            ]
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

    public function majixShowFreshBox($aConfig = [], $aTags = [])
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
    public function majixShowBox($aConfig = [], $aTags = [])
    {
        if ('EID' !== tx_mkforms_util_Div::getEnvExecMode()) {
            $aEventsBefore = array_keys($this->oForm->aRdtEvents);
        }

        $aChildsBag = $this->renderChildsBag();
        $aChildsBag = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aChildsBag, $aTags);

        if ('EID' !== tx_mkforms_util_Div::getEnvExecMode()) {
            $aEventsAfter = array_keys($this->oForm->aRdtEvents);
            $aAddedKeys = array_diff($aEventsAfter, $aEventsBefore);
            $aAddedEvents = [];
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
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_modalbox/api/class.tx_rdtmodalbox.php'];
}

<?php
/**
 * Plugin 'rdt_modalbox2' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_modalbox2_Main extends formidable_mainrenderlet
{
    public $aLibs = array(
        'rdt_modalbox2_class' => 'res/js/modalbox2.js',
        'rdt_modalbox2_lib_class' => 'res/js/modalbox1.6.0/modalbox.js',
    );

    public $bCustomIncludeScript = true;
    public $sMajixClass = 'ModalBox2';

    public function _render()
    {
        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts();

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

    public function majixShowBox($aConfig = array(), $aTags = array())
    {
        if ('EID' !== tx_mkforms_util_Div::getEnvExecMode()) {
            $aEventsBefore = array_keys($this->oForm->aRdtEvents);
        }

        $aChildsBag = $this->renderChildsBag();
        $aChildsBag = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aChildsBag, $aTags);

        if ('EID' !== tx_mkforms_util_Div::getEnvExecMode()) {
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
            $aConfig['postinit'] = $this->oForm->aPostInitTasks;
        } else {
            // specific to this renderlet
            // as events have to be attached to the HTML
            // after the execution of the majix tasks
            // in that case, using the modalbox's afterLoad event handler
            $aConfig['attachevents'] = $this->oForm->aRdtEventsAjax;
            $aConfig['postinit'] = $this->oForm->aPostInitTasksAjax;
            $this->oForm->aRdtEventsAjax = array();
            $this->oForm->aPostInitTasksAjax = array();
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

    public function majixCloseBox($aOptions = false)
    {
        return $this->buildMajixExecuter(
            'closeBox',
            $aOptions
        );
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

    public function majixResizeToContent()
    {
        return $this->buildMajixExecuter(
            'resizeToContent'
        );
    }

    public function majixResizeToInclude($sHtmlId)
    {
        return $this->buildMajixExecuter(
            'resizeToInclude',
            $sHtmlId
        );
    }

    // this has to be static !!!
    public static function loaded(&$aParams)
    {
        $aParams['form']->getJSLoader()->loadScriptaculous();
        $sCss = $aParams['form']->toWebPath('EXT:ameos_formidable/api/base/rdt_modalbox2/res/js/modalbox1.6.0/modalbox.css');
        $aParams['form']->additionalHeaderData(
            '<link rel="stylesheet" type="text/css" href="'.$sCss.'" />'
        );
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_modalbox2/api/class.tx_rdtmodalbox2.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_modalbox2/api/class.tx_rdtmodalbox2.php'];
}

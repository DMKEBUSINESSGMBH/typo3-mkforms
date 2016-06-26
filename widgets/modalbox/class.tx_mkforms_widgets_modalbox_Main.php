<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_modalbox_Main extends formidable_mainrenderlet
{

    var $aLibs = array(
        "rdt_modalbox_class" => "res/js/modalbox.js",
    );

    var $bCustomIncludeScript = true;
    var $sMajixClass = "ModalBox";

    function _render()
    {

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            array(
                "followScrollVertical" => $this->defaultTrue("/followscrollvertical"),
                "followScrollHorizontal" => $this->_defaultTrue("/followscrollhorizontal"),
            )
        );

        return "";
    }

    function _renderReadOnly()
    {
        return $this->_render();
    }
    function _readOnly()
    {
        return true;
    }
    function _renderOnly()
    {
        return true;
    }
    function mayHaveChilds()
    {
        return true;
    }

    function majixShowFreshBox($aConfig = array(), $aTags = array())
    {

        $this->initChilds(
            true    // existing renderlets in $this->oForm->aORenderlets will be overwritten
        );  // re-init childs before rendering

        $this->oForm->oDataHandler->refreshAllData();

        return $this->majixShowBox($aConfig, $aTags);
    }

    function majixShowBox($aConfig = array(), $aTags = array())
    {

        if (tx_mkforms_util_Div::getEnvExecMode() !== "EID") {
            $aEventsBefore = array_keys($this->oForm->aRdtEvents);
        }

        $aChildsBag = $this->renderChildsBag();
        $aChildsBag = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aChildsBag, $aTags);

        if (tx_mkforms_util_Div::getEnvExecMode() !== "EID") {
            $aEventsAfter = array_keys($this->oForm->aRdtEvents);
            $aAddedKeys = array_diff($aEventsAfter, $aEventsBefore);
            $aAddedEvents = array();
            reset($aAddedKeys);
            while (list(, $sKey) = each($aAddedKeys)) {
                $aAddedEvents[$sKey] = $this->oForm->aRdtEvents[$sKey];
                unset($this->oForm->aRdtEvents[$sKey]);
                // unset because if rendered in a lister,
                    // we need to be able to detect the new events even if they were already declared by other loops in the lister
            }

            $aConfig["attachevents"] = $aAddedEvents;
        }

        $sCompiledChilds = $this->renderChildsCompiled(
            $aChildsBag
        );

        $aConfig["html"] = $sCompiledChilds;

        return $this->buildMajixExecuter(
            "showBox",
            $aConfig
        );
    }

    function majixCloseBox()
    {

        return $this->buildMajixExecuter(
            "closeBox"
        );
    }

    function loadModalBox(&$oForm)
    {
        $oJsLoader = $this->getForm()->getJSLoader();
        $oJsLoader->loadScriptaculous();

        $sPath = Tx_Rnbase_Utility_T3General::getIndpEnv("TYPO3_SITE_URL") . tx_rnbase_util_Extensions::siteRelPath("ameos_formidable") . "api/base/rdt_modalbox/res/js/modalbox.js";

        $oForm->additionalHeaderData(
            "<script type=\"text/javascript\" src=\"" . $oJsLoader->getScriptPath($sPath) . "\"></script>",
            "rdt_modalbox_class"
        );
    }

    // this has to be static !!!
    function loaded(&$aParams)
    {
        $aParams["form"]->getJSLoader()->loadScriptaculous();
    }

    function majixRepaint()
    {
        return $this->buildMajixExecuter(
            "repaint",
            $this->renderChildsCompiled(
                $this->renderChildsBag()
            )
        );
    }
}

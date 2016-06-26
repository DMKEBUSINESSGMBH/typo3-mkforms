<?php
/**
 * Plugin 'rdt_modalbox2' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_modalbox2_Main extends formidable_mainrenderlet
{

    var $aLibs = array(
        "rdt_modalbox2_class" => "res/js/modalbox2.js",
        "rdt_modalbox2_lib_class" => "res/js/modalbox1.6.0/modalbox.js",
    );

    var $bCustomIncludeScript = true;
    var $sMajixClass = "ModalBox2";

    function _render()
    {
        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts();

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
            $aConfig["postinit"] = $this->oForm->aPostInitTasks;
        } else {
            # specific to this renderlet
                # as events have to be attached to the HTML
                # after the execution of the majix tasks
                    # in that case, using the modalbox's afterLoad event handler
            $aConfig["attachevents"] = $this->oForm->aRdtEventsAjax;
            $aConfig["postinit"] = $this->oForm->aPostInitTasksAjax;
            $this->oForm->aRdtEventsAjax = array();
            $this->oForm->aPostInitTasksAjax = array();
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

    function majixCloseBox($aOptions = false)
    {
        return $this->buildMajixExecuter(
            "closeBox",
            $aOptions
        );
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

    function majixResizeToContent()
    {
        return $this->buildMajixExecuter(
            "resizeToContent"
        );
    }

    function majixResizeToInclude($sHtmlId)
    {
        return $this->buildMajixExecuter(
            "resizeToInclude",
            $sHtmlId
        );
    }

    // this has to be static !!!
    function loaded(&$aParams)
    {
        $aParams["form"]->getJSLoader()->loadScriptaculous();
        $sCss = $aParams["form"]->toWebPath("EXT:ameos_formidable/api/base/rdt_modalbox2/res/js/modalbox1.6.0/modalbox.css");
        $aParams["form"]->additionalHeaderData(
            "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $sCss . "\" />"
        );
    }
}

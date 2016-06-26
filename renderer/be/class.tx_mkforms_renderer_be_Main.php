<?php
/**
 * Plugin 'rdr_be' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_renderer_be_Main extends formidable_mainrenderer
{

    function _render($aRendered)
    {

        $this->oForm->_debug($aRendered, "RENDERER BACKEND - rendered elements array");
        $sForm = $this->_collate($aRendered);

        if (!$this->oForm->oDataHandler->_allIsValid()) {
            $sValidationErrors = implode("<br />", $this->oForm->_aValidationErrors) . "<hr />";
        }

        return $this->_wrapIntoForm($sValidationErrors . $sForm);
    }

    function _collate($aHtml)
    {

        $sHtml = "";

        if (is_array($aHtml) && count($aHtml) > 0) {
            reset($aHtml);

            while (list($sName, $aChannels) = each($aHtml)) {
                $sHtml .= "\n<p>" . str_replace("{PARENTPATH}", $this->oForm->_getParentExtSitePath(), $aChannels["__compiled"]) . "</p>\n";
            }
        }

        return $sHtml;
    }
}

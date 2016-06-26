<?php
/**
 * Plugin 'rdt_dewplayer' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_dewplayer_Main extends formidable_mainrenderlet
{

    function _render()
    {

        $sLabel = $this->getLabel();

        $sPath = $this->_getPath();
        $sHtmlId = $this->_getElementHtmlId();
        $sHtmlName = $this->_getElementHtmlName();
        $sAddParams = $this->_getAddInputParams();

        $bAutoStart = $this->oForm->_defaultFalse("/autostart", $this->aElement);
        $bAutoReplay = $this->oForm->_defaultFalse("/autoreplay", $this->aElement);
        $sBgColor = (($sTempColor = $this->_navConf("/bgcolor")) !== false) ? $sTempColor : "FFFFFF";

        $sMoviePath = $this->sExtWebPath . "res/dewplayer.swf";

        $sColor = str_replace("#", "", $sBgColor);
        $sMoviePath .= "?bgcolor=" . $sColor;
        $sFlashParams .= '<param name="bgcolor" value="' . $sColor . '" />';

        $sFlashParams = "";

        if ($bAutoStart) {
            $sMoviePath .= "&autostart=1";
            $sFlashParams .= '<param name="autostart" value="1" />';
        }

        if ($bAutoReplay) {
            $sMoviePath .= "&autoreplay=1";
            $sFlashParams .= '<param name="autoreplay" value="1" />';
        }



        $sMoviePath .= "&mp3=" . rawurlencode($sPath);

        $sInput =<<< FLASHOBJECT

			<object
				name=		"{$sHtmlName}"
				id=			"{$sHtmlId}"
				codebase=	"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0"
				type=		"application/x-shockwave-flash"
				data=		"{$sMoviePath}"
				width=		"200"
				height=		"20"
				align=		"middle"
				{$sAddParams}>

				<param name="allowScriptAccess" value="sameDomain" />
				<param name="movie" value="{$sMoviePath}" />
				<param name="quality" value="high" />
				{$sFlashParams}

			</object>

FLASHOBJECT;



        $aHtmlBag = array(
            "__compiled" => $this->_displayLabel($sLabel) . $sInput,
            "input" => $sInput,
            "mp3." => array(
                "file" => $sPath,
            )
        );

        return $aHtmlBag;
    }

    function _renderOnly()
    {
        return true;
    }

    function _getPath()
    {

        if (($sPath = $this->_navConf("/path")) !== false) {
            if ($this->oForm->isRunneable($sPath)) {
                $sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
            }

            if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sPath, "EXT:")) {
                $sPath = Tx_Rnbase_Utility_T3General::getIndpEnv("TYPO3_SITE_URL") .
                    str_replace(
                        Tx_Rnbase_Utility_T3General::getIndpEnv("TYPO3_DOCUMENT_ROOT"),
                        "",
                        Tx_Rnbase_Utility_T3General::getFileAbsFileName($sPath)
                    );
            }
        }

        return $sPath;
    }
}

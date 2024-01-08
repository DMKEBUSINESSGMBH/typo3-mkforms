<?php
/**
 * Plugin 'rdt_dewplayer' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_dewplayer_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $sLabel = $this->getLabel();

        $sPath = $this->_getPath();
        $sHtmlId = $this->_getElementHtmlId();
        $sHtmlName = $this->_getElementHtmlName();
        $sAddParams = $this->_getAddInputParams();

        $bAutoStart = $this->oForm->_defaultFalse('/autostart', $this->aElement);
        $bAutoReplay = $this->oForm->_defaultFalse('/autoreplay', $this->aElement);
        $sBgColor = (false !== ($sTempColor = $this->_navConf('/bgcolor'))) ? $sTempColor : 'FFFFFF';

        $sMoviePath = TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(
            TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:mkforms/Resources/Public/Flash/dewplayer.swf')
        );

        $sColor = str_replace('#', '', $sBgColor);
        $sMoviePath .= '?bgcolor='.$sColor;

        $sFlashParams = '';

        if ($bAutoStart) {
            $sMoviePath .= '&autostart=1';
            $sFlashParams .= '<param name="autostart" value="1" />';
        }

        if ($bAutoReplay) {
            $sMoviePath .= '&autoreplay=1';
            $sFlashParams .= '<param name="autoreplay" value="1" />';
        }

        $sMoviePath .= '&mp3='.rawurlencode($sPath);

        $sInput = <<< FLASHOBJECT

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

        $aHtmlBag = [
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'input' => $sInput,
            'mp3.' => [
                'file' => $sPath,
            ],
        ];

        return $aHtmlBag;
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function _getPath()
    {
        if (false !== ($sPath = $this->_navConf('/path'))) {
            if ($this->oForm->isRunneable($sPath)) {
                $sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
            }

            if (Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sPath, 'EXT:')) {
                $sPath = Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL').
                    str_replace(
                        Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_DOCUMENT_ROOT'),
                        '',
                        Sys25\RnBase\Utility\T3General::getFileAbsFileName($sPath)
                    );
            }
        }

        return $sPath;
    }
}

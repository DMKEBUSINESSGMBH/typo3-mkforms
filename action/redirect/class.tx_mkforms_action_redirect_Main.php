<?php

/**
 * Plugin 'act_redct' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_action_redirect_Main extends formidable_mainactionlet
{
    public function _doTheMagic($aRendered, $sForm)
    {
        if (!$this->getForm()->getDataHandler()->_allIsValid()) {
            return;
        }
        if (!$this->shouldProcess()) {
            return;
        }

        if (false !== ($mPage = $this->_navConf('/pageid'))) {
            $mPage = $this->callRunneable($mPage);
            $sUrl = $this->getForm()->getCObj()->typoLink_URL(['parameter' => $mPage]);
            if (!\Sys25\RnBase\Utility\T3General::isFirstPartOfStr($sUrl, 'http://') && '' !== trim($GLOBALS['TSFE']->baseUrl)) {
                $sUrl = tx_mkforms_util_Div::removeEndingSlash($GLOBALS['TSFE']->baseUrl).'/'.$sUrl;
            }
        } else {
            $sUrl = $this->_navConf('/url');
            $sUrl = $this->callRunneable($sUrl);
        }

        if ($this->getForm()->isTestMode()) {
            return $sUrl;
        } else {
            if (is_string($sUrl) && '' !== trim($sUrl)) {
                header('HTTP/1.1 303 See Other');
                header('Status: 303 See Other');
                header('Location: '.$sUrl);
                exit;
            }
        }
    }
}

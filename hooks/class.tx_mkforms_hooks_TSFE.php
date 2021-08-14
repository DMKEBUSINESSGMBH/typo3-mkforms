<?php

class tx_mkforms_hooks_TSFE implements Tx_Rnbase_Interface_Singleton
{
    public function contentPostProc_output()
    {
        if (isset($GLOBALS['tx_ameosformidable']) && isset($GLOBALS['tx_ameosformidable']['headerinjection'])) {
            reset($GLOBALS['tx_ameosformidable']['headerinjection']);
            foreach ($GLOBALS['tx_ameosformidable']['headerinjection'] as $aHeaderSet) {
                $GLOBALS['TSFE']->content = str_replace(
                    $aHeaderSet['marker'],
                    implode("\n", $aHeaderSet['headers'])."\n".$aHeaderSet['marker'],
                    $GLOBALS['TSFE']->content
                );
            }
        }
    }
}

<?php

class user_ameosformidable_userfuncs
{
    public function getAdditionalHeaderData()
    {
        $aRes = [];
        if (isset($GLOBALS['tx_ameosformidable']) && isset($GLOBALS['tx_ameosformidable']['headerinjection'])) {
            reset($GLOBALS['tx_ameosformidable']['headerinjection']);
            foreach ($GLOBALS['tx_ameosformidable']['headerinjection'] as $aHeaderSet) {
                $aRes[] = implode("\n", $aHeaderSet['headers']);
            }
        }

        reset($aRes);

        return implode('', $aRes);
    }
}

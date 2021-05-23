<?php

/**
 * Plugin 'dh_void' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_dh_void_Main extends formidable_maindatahandler
{
    /**
     * @param bool $bShouldProcess
     *
     * @return string
     */
    public function _doTheMagic($bShouldProcess = true)
    {
        if ($bShouldProcess && $this->_allIsValid()) {
            $this->oForm->_debug('void do nothing with data', 'DATAHANDLER VOID - EXECUTION');
        }

        return '';
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/dh_void/api/class.tx_dhvoid.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/dh_void/api/class.tx_dhvoid.php'];
}

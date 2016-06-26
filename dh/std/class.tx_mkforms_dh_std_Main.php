<?php

/**
 * Plugin 'dh_std' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_dh_std_Main extends formidable_maindatahandler
{

    function _doTheMagic($bShouldProcess = true)
    {

        if ($bShouldProcess && $this->_allIsValid()) {
            $this->oForm->_debug(
                array(
                    "DATA" => $this->getFormData(),
                ),
                "DATAHANDLER STANDARD - EXECUTION"
            );
        }
    }
}

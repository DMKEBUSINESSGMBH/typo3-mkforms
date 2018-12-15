<?php

/**
 * Plugin 'act_userobj' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_action_userobj_Main extends formidable_mainactionlet
{
    /**
     * @param array $aRendered
     * @param string $sForm
     */
    public function _doTheMagic($aRendered, $sForm)
    {
        if ($this->oForm->oDataHandler->_allIsValid()) {
            $this->callRunneable($this->aElement);
        }

        return '';
    }
}

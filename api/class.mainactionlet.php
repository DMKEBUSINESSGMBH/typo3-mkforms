<?php

class formidable_mainactionlet extends formidable_mainobject
{
    public function _doTheMagic($aRendered, $sForm)
    {
    }

    /**
     * Legt fest, ob das actionlet verarbeitet wird. Wenn false wird es komplett ignoriert
     *
     * @return bool
     */
    protected function shouldProcess()
    {
        $mProcess = $this->_navConf('/process');

        if ($mProcess !== false) {
            if ($this->getForm()->isRunneable($mProcess)) {
                $mProcess = $this->getForm()->getRunnable()->callRunnableWidget($this, $mProcess);

                if ($mProcess === false) {
                    return false;
                }
            } elseif ($this->getForm()->_isFalseVal($mProcess)) {
                return false;
            }
        }

        return true;
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainactionlet.php']
) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainactionlet.php']);
}

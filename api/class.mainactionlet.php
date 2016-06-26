<?php

class formidable_mainactionlet extends formidable_mainobject
{

    function _doTheMagic()
    {
    }

    /**
     * Legt fest, ob das actionlet verarbeitet wird. Wenn false wird es komplett ignoriert
     *
     * @return boolean
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

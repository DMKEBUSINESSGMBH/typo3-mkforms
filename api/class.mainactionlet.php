<?php

class formidable_mainactionlet extends formidable_mainobject
{
    public function _doTheMagic($aRendered, $sForm)
    {
    }

    /**
     * Legt fest, ob das actionlet verarbeitet wird. Wenn false wird es komplett ignoriert.
     *
     * @return bool
     */
    protected function shouldProcess()
    {
        $mProcess = $this->getConfigValue('/process');

        if (false !== $mProcess) {
            if ($this->getForm()->isRunneable($mProcess)) {
                $mProcess = $this->getForm()->getRunnable()->callRunnableWidget($this, $mProcess);

                if (false === $mProcess) {
                    return false;
                }
            } elseif ($this->getForm()->_isFalseVal($mProcess)) {
                return false;
            }
        }

        return true;
    }
}

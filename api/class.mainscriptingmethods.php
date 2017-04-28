<?php

class formidable_mainscriptingmethods
{
    public function _init(&$oForm)
    {
        $this->oForm =& $oForm;
    }

    /**
     * Returns the form
     *
     * @return tx_ameosformidable
     */
    protected function getForm()
    {
        return $this->oForm;
    }

    public function process($sMethod, $mData, $sArgs)
    {
        $aParams = $this->oForm->getTemplateTool()->parseTemplateMethodArgs($sArgs);
        $sMethodName = strtolower('method_' . $sMethod);

        if (method_exists($this, $sMethodName)) {
            return $this->$sMethodName($mData, $aParams);
        } else {
            if (is_object($mData) && is_string($sMethod) && method_exists($mData, $sMethod)) {
                return $mData->{$sMethod}($aParams, $this->oForm);
            }
        }

        return AMEOSFORMIDABLE_LEXER_FAILED;
    }
} // END class formidable_mainscriptingmethods
